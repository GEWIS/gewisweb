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

        foreach ($files as $file) {
            match (
                $this->storeOne(
                    $album,
                    $file,
                )
            ) {
                'created' => ++$created,
                'duplicate' => ++$duplicates,
                default => ++$failed,
            };
        }

        if ($created > 0) {
            $this->messageBus->dispatch(new GenerateAlbumCoverMessage((int) $album->getId()));
        }

        return [
            'created' => $created,
            'duplicates' => $duplicates,
            'failed' => $failed,
        ];
    }

    private function storeOne(
        Album $album,
        UploadedFile $file,
    ): string {
        $stored = null;

        try {
            // A real image? Also the width/height source. Rejects anything neither reader can decode.
            $aspectRatio = $this->readAspectRatio($file->getPathname());
            if (null === $aspectRatio) {
                return 'failed';
            }

            // store() re-validates the MIME type and size limit and de-duplicates by content hash. Photos are scoped
            // per album, so the same bytes in another album get their own path and never share a file.
            $stored = $this->fileStorage->store(
                StorageNamespace::PhotoOriginal,
                $file->getPathname(),
                (string) $album->getId(),
            );

            if (
                null !== $this->photoRepository->getPhotoByData(
                    $stored->path,
                    $album,
                )
            ) {
                return 'duplicate';
            }

            $photo = new Photo();
            $photo->setAlbum($album);
            $photo->setPath($stored->path);
            $photo->setDateTime(new DateTime());
            $photo->setAspectRatio($aspectRatio);
            $this->metadataReader->read($file->getPathname())->applyTo($photo);

            $this->entityManager->persist($photo);
            $this->entityManager->flush();

            $this->messageBus->dispatch(new ProcessImageVariantsMessage($stored->path, ImageProfile::AlbumPhoto));

            return 'created';
        } catch (Throwable) {
            // Reclaim the bytes we freshly wrote this call; a pre-existing (deduplicated) file is left alone.
            if (
                null !== $stored
                && !$stored->deduplicated
            ) {
                $this->fileStorage->remove($stored->path);
            }

            return 'failed';
        }
    }

    /**
     * The photo's aspect ratio (height / width). getimagesize is the cheap header read and doubles as the "is a real
     * image" check, but it cannot decode every accepted format (notably HEIC), so fall back to the image backend
     * (libvips), which can. Returns null when neither can read the file, i.e. it is not a usable image.
     */
    private function readAspectRatio(string $path): ?float
    {
        $dimensions = getimagesize($path);
        if (
            false !== $dimensions
            && $dimensions[0] > 0
        ) {
            return $dimensions[1] / $dimensions[0];
        }

        try {
            $image = $this->imageManagerProvider->create()
                ->decodeBinary((string) file_get_contents($path))
                ->orient();

            return $image->width() > 0
                ? $image->height() / $image->width()
                : null;
        } catch (Throwable) {
            return null;
        }
    }
}
