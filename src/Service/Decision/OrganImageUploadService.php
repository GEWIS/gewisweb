<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\StorageNamespace;
use App\Service\Application\AbstractImageUploadService;
use App\Service\Application\FileStorage;
use App\Service\Application\ImageManagerProvider;
use Intervention\Image\Encoders\WebpEncoder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

use function file_put_contents;
use function max;
use function min;
use function round;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * The two halves of putting an image on a body's page: taking the file that was uploaded, and cutting the part of it
 * the body chose out into an image of its own.
 *
 * The original is kept as well as the cut, so the crop can be moved later without asking for the file again. The crop
 * arrives as fractions of the original rather than pixels, which is what lets the picker work on whatever rendition
 * of the original happened to be shown.
 */
final readonly class OrganImageUploadService extends AbstractImageUploadService
{
    /** WebP at this quality is indistinguishable from the source at the sizes a page shows. */
    private const int QUALITY = 90;

    public function __construct(
        FileStorage $fileStorage,
        MessageBusInterface $messageBus,
        private ImageManagerProvider $imageManagerProvider,
    ) {
        parent::__construct(
            $fileStorage,
            $messageBus,
        );
    }

    /**
     * Store an uploaded image as it arrived. The crop is chosen against this afterwards, which is why its renditions
     * are generated too.
     *
     * @return string|null the stored path, or null when the file could not be stored
     */
    public function uploadSource(UploadedFile $file): ?string
    {
        return $this->storeAndQueue(
            StorageNamespace::OrganImage,
            $file->getPathname(),
            null,
            ImageProfile::OrganImage,
        );
    }

    /**
     * Cut the chosen part out of a stored original and store that as an image of its own, so what the page is served
     * has the shape it is shown in and no rendition has to guess at a crop.
     *
     * @param array<string, float> $crop x, y, width and height as fractions of the original
     *
     * @return string|null the stored path, or null when the crop could not be applied
     */
    public function applyCrop(
        string $sourcePath,
        array $crop,
    ): ?string {
        $localPath = tempnam(
            sys_get_temp_dir(),
            'organ-crop-',
        );
        if (false === $localPath) {
            return null;
        }

        try {
            $image = $this->imageManagerProvider->create()
                ->decodeBinary($this->fileStorage->read($sourcePath))
                ->orient();

            // The fractions are the picker's, so they are held to the image rather than trusted: a rectangle that ran
            // off an edge is pulled back inside it, and one that came out empty is refused.
            $width = $this->pixels(
                $crop['width'] ?? 1.0,
                $image->width(),
            );
            $height = $this->pixels(
                $crop['height'] ?? 1.0,
                $image->height(),
            );
            if (
                0 === $width
                || 0 === $height
            ) {
                return null;
            }

            $left = min(
                $this->pixels(
                    $crop['x'] ?? 0.0,
                    $image->width(),
                ),
                $image->width() - $width,
            );
            $top = min(
                $this->pixels(
                    $crop['y'] ?? 0.0,
                    $image->height(),
                ),
                $image->height() - $height,
            );

            $encoded = $image->crop(
                $width,
                $height,
                max(
                    0,
                    $left,
                ),
                max(
                    0,
                    $top,
                ),
            )->encode(new WebpEncoder(
                quality: self::QUALITY,
                strip: true,
            ));

            file_put_contents(
                $localPath,
                $encoded->toString(),
            );

            return $this->storeAndQueue(
                StorageNamespace::OrganImage,
                $localPath,
                null,
                ImageProfile::OrganImage,
            );
        } catch (Throwable) {
            return null;
        } finally {
            unlink($localPath);
        }
    }

    /**
     * A fraction of a dimension, as whole pixels inside it.
     */
    private function pixels(
        float $fraction,
        int $dimension,
    ): int {
        return (int) round(min(
            max(
                $fraction,
                0.0,
            ),
            1.0,
        ) * $dimension);
    }
}
