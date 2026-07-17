<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Message\Photo\GenerateAlbumCoverMessage;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Application\ImageManagerProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

use function file_get_contents;
use function getimagesize;
use function intval;
use function max;
use function min;
use function strval;

/**
 * Stores uploaded photos into an album, one at a time so a single bad file never aborts the batch. Each file is
 * validated (a real image), content-addressed into storage (which de-duplicates), skipped if the album already holds
 * the same bytes, and — only once its Photo is committed — queued for variant generation. After the batch the album
 * cover is queued for regeneration once.
 *
 * Aspect ratio comes from the image header; the remaining metadata (capture time, camera, GPS, ...) is read from the
 * original's EXIF, falling back to the upload time when the image carries none.
 */
final readonly class PhotoUploadService
{
    public function __construct(
        private FileStorage $fileStorage,
        private PhotoRepository $photoRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private PhotoMetadataReader $metadataReader,
        private ImageManagerProvider $imageManagerProvider,
    ) {
    }

    /**
     * @param UploadedFile[] $files
     *
     * @return array{created: int, duplicates: int, failed: int}
     */
    public function upload(
        Album $album,
        array $files,
    ): array {
        $created = 0;
        $duplicates = 0;
        $failed = 0;
        $captureTimes = [];

        foreach ($files as $file) {
            $result = $this->storeOne(
                $album,
                $file,
            );

            match ($result['status']) {
                'created' => ++$created,
                'duplicate' => ++$duplicates,
                default => ++$failed,
            };

            if (null === $result['capturedAt']) {
                continue;
            }

            $captureTimes[] = $result['capturedAt'];
        }

        if ($created > 0) {
            $this->messageBus->dispatch(new GenerateAlbumCoverMessage(intval($album->getId())));
            // Only genuine EXIF capture times may adjust the album's date range; a no-EXIF photo (scan, export) keeps
            // the upload time for ordering but must never drag a board-curated range to today.
            $this->widenDateRange(
                $album,
                $captureTimes,
            );
        }

        return [
            'created' => $created,
            'duplicates' => $duplicates,
            'failed' => $failed,
        ];
    }

    /**
     * Widen the album's date range to span the given EXIF capture times, extending each bound only outward. A
     * board-set range is never contracted, and an album whose upload carried no EXIF dates keeps its existing range.
     *
     * @param DateTime[] $captureTimes
     */
    private function widenDateRange(
        Album $album,
        array $captureTimes,
    ): void {
        if ([] === $captureTimes) {
            return;
        }

        $earliest = min($captureTimes);
        $latest = max($captureTimes);
        $changed = false;

        $start = $album->getStartDateTime();
        if (
            null === $start
            || $earliest < $start
        ) {
            $album->setStartDateTime($earliest);
            $changed = true;
        }

        $end = $album->getEndDateTime();
        if (
            null === $end
            || $latest > $end
        ) {
            $album->setEndDateTime($latest);
            $changed = true;
        }

        if (!$changed) {
            return;
        }

        $this->entityManager->flush();
    }

    /**
     * @return array{status: string, capturedAt: ?DateTime} status is created|duplicate|failed; capturedAt is the
     *                                                       photo's EXIF capture time, present only when it carried one
     */
    private function storeOne(
        Album $album,
        UploadedFile $file,
    ): array {
        $stored = null;

        try {
            // Read the EXIF once: the uploader needs the orientation to size the photo, and the rest to fill its
            // columns once it is committed.
            $metadata = $this->metadataReader->read($file->getPathname());

            // A real image? Also the width/height source. Rejects anything neither reader can decode.
            $aspectRatio = $this->readAspectRatio(
                $file->getPathname(),
                $metadata->swapsAxes,
            );
            if (null === $aspectRatio) {
                return [
                    'status' => 'failed',
                    'capturedAt' => null,
                ];
            }

            // store() re-validates the MIME type and size limit and de-duplicates by content hash. Photos are scoped
            // per album, so the same bytes in another album get their own path and never share a file.
            $stored = $this->fileStorage->store(
                StorageNamespace::PhotoOriginal,
                $file->getPathname(),
                strval($album->getId()),
            );

            if (
                null !== $this->photoRepository->getPhotoByData(
                    $stored->path,
                    $album,
                )
            ) {
                return [
                    'status' => 'duplicate',
                    'capturedAt' => null,
                ];
            }

            $photo = new Photo();
            $photo->setAlbum($album);
            $photo->setPath($stored->path);
            // A non-EXIF photo still needs a (non-nullable) timestamp for ordering; the upload time is the fallback,
            // and applyTo() overrides it only when EXIF actually carried a capture time.
            $photo->setDateTime(new DateTime());
            $photo->setAspectRatio($aspectRatio);
            $metadata->applyTo($photo);

            $this->entityManager->persist($photo);
            $this->entityManager->flush();

            $this->messageBus->dispatch(new ProcessImageVariantsMessage($stored->path, ImageProfile::AlbumPhoto));

            return [
                'status' => 'created',
                'capturedAt' => $metadata->dateTime,
            ];
        } catch (Throwable) {
            // Reclaim the bytes we freshly wrote this call; a pre-existing (deduplicated) file is left alone.
            if (
                null !== $stored
                && !$stored->deduplicated
            ) {
                $this->fileStorage->remove($stored->path);
            }

            return [
                'status' => 'failed',
                'capturedAt' => null,
            ];
        }
    }

    /**
     * The photo's aspect ratio (height / width). getimagesize is the cheap header read and doubles as the "is a real
     * image" check, but it cannot decode every accepted format (notably HEIC), so fall back to the image backend
     * (libvips), which can. Returns null when neither can read the file, i.e. it is not a usable image.
     */
    private function readAspectRatio(
        string $path,
        bool $swapsAxes,
    ): ?float {
        $dimensions = getimagesize($path);
        if (
            false !== $dimensions
            && $dimensions[0] > 0
            && $dimensions[1] > 0
        ) {
            // getimagesize reports the stored pixel dimensions and ignores the EXIF orientation tag, yet the served
            // variants (and the libvips fallback below) are EXIF-oriented, so a quarter-turn orientation swaps the
            // displayed axes. Swap them here too, or a rotated JPEG stores a landscape ratio for a portrait thumbnail.
            [
                $width, $height
            ] = $swapsAxes
                ? [
                    $dimensions[1],
                    $dimensions[0],
                ]
                : [
                    $dimensions[0],
                    $dimensions[1],
                ];

            return $height / $width;
        }

        try {
            $image = $this->imageManagerProvider->create()
                ->decodeBinary(strval(file_get_contents($path)))
                ->orient();

            return $image->width() > 0
                ? $image->height() / $image->width()
                : null;
        } catch (Throwable) {
            return null;
        }
    }
}
