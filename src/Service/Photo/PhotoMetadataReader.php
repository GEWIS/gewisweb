<?php

declare(strict_types=1);

namespace App\Service\Photo;

use function exif_read_data;
use function function_exists;
use function is_array;

/**
 * Reads a photo's EXIF into a {@see PhotoMetadata}. Returns an empty instance when ext-exif is absent or the image
 * carries no EXIF, so storing a photo never depends on metadata being present.
 */
final readonly class PhotoMetadataReader
{
    /**
     * @param resource|string $file a readable stream or a filename, as accepted by exif_read_data()
     */
    public function read(mixed $file): PhotoMetadata
    {
        if (!function_exists('exif_read_data')) {
            return PhotoMetadata::empty();
        }

        $exif = @exif_read_data($file);

        return is_array($exif)
            ? PhotoMetadata::fromExif($exif)
            : PhotoMetadata::empty();
    }
}
