<?php

declare(strict_types=1);

namespace Photo\Service;

use DateTime;
use Photo\Model\Photo as PhotoModel;

use function array_map;
use function count;
use function exif_read_data;
use function explode;
use function floatval;
use function is_string;
use function round;
use function sprintf;
use function str_contains;

/**
 * Metadata service. This service implements all functionality related to
 * gathering metadata about photos.
 */
class Metadata
{
    /**
     * Populates the metadata of a photo based on the EXIF data of the photo.
     *
     * @param PhotoModel $photo the photo to add the metadata to
     * @param string     $path  The path where the actual image file is stored
     *
     * @return PhotoModel the photo with the added metadata
     */
    public function populateMetadata(
        PhotoModel $photo,
        string $path,
    ): PhotoModel {
        $exif = exif_read_data($path, 'EXIF');

        if ($exif) {
            $photo->setArtist($exif['Artist'] ?? null);
            $photo->setCamera($exif['Model'] ?? null);

            $photo->setDateTime(new DateTime($exif['DateTimeOriginal'] ?? 'now'));

            if (isset($exif['Flash'])) {
                $photo->setFlash(0 !== $exif['Flash']);
            }

            if (isset($exif['FocalLength'])) {
                $photo->setFocalLength($this->frac2dec($exif['FocalLength']));
            }

            if (isset($exif['ExposureTime'])) {
                $photo->setExposureTime($this->frac2dec($exif['ExposureTime']));
            }

            if (isset($exif['ShutterSpeedValue'])) {
                $photo->setShutterSpeed($this->exifGetShutter($exif['ShutterSpeedValue']));
            }

            if (isset($exif['ShutterSpeedValue'])) {
                $photo->setAperture($this->exifGetFstop($exif['ApertureValue']));
            }

            $photo->setIso($exif['ISOSpeedRatings'] ?? null);

            if (isset($exif['GPSLongitude']) && isset($exif['GPSLongitudeRef'])) {
                $photo->setLongitude($this->exifGpsToCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']));
            }

            if (isset($exif['GPSLatitude']) && isset($exif['GPSLatitudeRef'])) {
                $photo->setLatitude($this->exifGpsToCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']));
            }
        } else {
            // We must have a date/time for a photo
            // Since no date is known, we use the current one
            $photo->setDateTime(new DateTime());
        }

        return $photo;
    }

    /*
     * NOTE: Most code in the following part is copied from
     * the old site, mostly because I lack knowledge in photography.
     */

    /**
     * Convert a string representing a rational number to a string representing
     * the corresponding decimal approximation.
     *
     * @param string $str the rational number, represented as num+'/'+den
     *
     * @return float|int the decimal number, represented as float
     */
    private static function frac2dec(string $str): float|int
    {
        if (!str_contains($str, '/')) {
            return (float) $str;
        }

        [$n, $d] = explode('/', $str);

        return (int) $n / (int) $d; //I assume stuff like '234/0' is not supported by EXIF.
    }

    /**
     * Computes the shutter speed from the exif data.
     *
     * @param string $shutterSpeed the shutter speed as listed in the photo's exif data
     */
    private function exifGetShutter(string $shutterSpeed): ?string
    {
        $apex = $this->frac2dec($shutterSpeed);
        $shutter = 2 ** (-$apex);
        if (0 === $shutter) {
            return null;
        }

        if ($shutter >= 1) {
            return round($shutter) . 's';
        }

        return '1/' . round(1 / $shutter) . 's';
    }

    /**
     * Computes the relative aperture from the exif data.
     *
     * @param string $apertureValue the aperture value as listed in the photo's exif data
     */
    private function exifGetFstop(string $apertureValue): ?string
    {
        $apex = $this->frac2dec($apertureValue);
        $fstop = 2 ** ($apex / 2);
        if (0 === $fstop) {
            return null;
        }

        return 'f/' . sprintf('%01.1f', $fstop);
    }

    /**
     * Computes the coordinate for a given exif GPS location.
     */
    private static function exifGpsToCoordinate(
        array|string $coordinate,
        string $hemisphere,
    ): float|int|null {
        if (empty($coordinate)) {
            return null;
        }

        if (is_string($coordinate)) {
            $coordinate = array_map('trim', explode(',', $coordinate));
        }

        for ($i = 0; $i < 3; ++$i) {
            $part = explode('/', $coordinate[$i]);
            if (1 === count($part)) {
                $coordinate[$i] = $part[0];
                continue;
            }

            if (2 === count($part)) {
                $coordinate[$i] = floatval($part[0]) / floatval($part[1]);
                continue;
            }

            $coordinate[$i] = 0;
        }

        [$degrees, $minutes, $seconds] = $coordinate;
        $sign = 'W' === $hemisphere || 'S' === $hemisphere ? -1 : 1;

        return $sign * ($degrees + $minutes / 60 + $seconds / 3600);
    }
}
