<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Photo\Photo;
use DateTime;

use function array_key_exists;
use function count;
use function explode;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function mb_strtolower;
use function round;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * Camera metadata read from a photo's EXIF, mapped onto the columns of {@see Photo}. Every column field is optional: a
 * photo without EXIF (or in a format that carries none) yields all-null columns. The orientation-derived {@see
 * $swapsAxes} flag is the exception the uploader consults to size the photo, and defaults to no swap.
 */
final readonly class PhotoMetadata
{
    private function __construct(
        public ?DateTime $dateTime,
        public ?string $artist,
        public ?string $camera,
        public ?bool $flash,
        public ?float $focalLength,
        public ?float $exposureTime,
        public ?string $shutterSpeed,
        public ?string $aperture,
        public ?int $iso,
        public ?float $latitude,
        public ?float $longitude,
        public bool $swapsAxes,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            false,
        );
    }

    /**
     * @param array<string, mixed> $exif a flat exif_read_data() result
     */
    public static function fromExif(array $exif): self
    {
        $exposureTime = self::rational($exif['ExposureTime'] ?? null);

        return new self(
            dateTime: self::dateTime($exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null),
            artist: self::string($exif['Artist'] ?? null),
            camera: self::camera(
                $exif['Make'] ?? null,
                $exif['Model'] ?? null,
            ),
            flash: array_key_exists(
                'Flash',
                $exif,
            ) ? 1 === (intval($exif['Flash']) & 1) : null,
            focalLength: self::positiveFloat(self::rational($exif['FocalLength'] ?? null)),
            exposureTime: $exposureTime,
            shutterSpeed: self::shutterSpeed($exposureTime),
            aperture: self::aperture(self::rational($exif['FNumber'] ?? null)),
            iso: self::iso($exif['ISOSpeedRatings'] ?? null),
            latitude: self::gps(
                $exif['GPSLatitude'] ?? null,
                $exif['GPSLatitudeRef'] ?? null,
                'S',
            ),
            longitude: self::gps(
                $exif['GPSLongitude'] ?? null,
                $exif['GPSLongitudeRef'] ?? null,
                'W',
            ),
            swapsAxes: self::orientationSwapsAxes($exif['Orientation'] ?? null),
        );
    }

    public function applyTo(Photo $photo): void
    {
        // Only override the capture time when EXIF actually carried one; the column is not nullable.
        if (null !== $this->dateTime) {
            $photo->setDateTime($this->dateTime);
        }

        $photo->setArtist($this->artist);
        $photo->setCamera($this->camera);
        $photo->setFlash($this->flash);
        $photo->setFocalLength($this->focalLength);
        $photo->setExposureTime($this->exposureTime);
        $photo->setShutterSpeed($this->shutterSpeed);
        $photo->setAperture($this->aperture);
        $photo->setIso($this->iso);
        $photo->setLatitude($this->latitude);
        $photo->setLongitude($this->longitude);
    }

    /**
     * Whether the EXIF orientation is a quarter turn (90° or 270°), which swaps the image's displayed width and height
     * relative to its stored pixels. Values 5–8 are the transposed/rotated quarter-turn cases; anything else (no tag,
     * or an unreadable value) leaves the axes as stored.
     */
    private static function orientationSwapsAxes(mixed $orientation): bool
    {
        return in_array(
            intval($orientation),
            [
                5,
                6,
                7,
                8,
            ],
            true,
        );
    }

    private static function string(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed
            ? null
            : $trimmed;
    }

    private static function camera(
        mixed $make,
        mixed $model,
    ): ?string {
        $make = self::string($make);
        $model = self::string($model);

        if (null === $model) {
            return $make;
        }

        if (null === $make) {
            return $model;
        }

        // The model usually already carries the brand (e.g. "Canon EOS 60D"), and the make sometimes spells it out
        // more fully than the model ("NIKON CORPORATION" for a "NIKON D3500" model). Treat the make's first word as
        // the brand and only prefix the make when the model starts with neither the whole make nor that brand.
        $brand = explode(
            ' ',
            $make,
        )[0];
        if (
            self::startsWith(
                $model,
                $make,
            )
            || self::startsWith(
                $model,
                $brand,
            )
        ) {
            return $model;
        }

        return $make . ' ' . $model;
    }

    /**
     * Case-insensitive {@see str_starts_with}: camera makers are inconsistent about the casing of make versus model.
     */
    private static function startsWith(
        string $haystack,
        string $needle,
    ): bool {
        return str_starts_with(
            mb_strtolower($haystack),
            mb_strtolower($needle),
        );
    }

    private static function dateTime(mixed $value): ?DateTime
    {
        $value = self::string($value);
        if (null === $value) {
            return null;
        }

        // createFromFormat rolls invalid components over rather than failing (the "0000:00:00 00:00:00" no-date
        // sentinel would become a real date), so reject any parse that reported a warning or error. Unlike a strict
        // string round-trip this still accepts valid but unpadded components (e.g. "2019:08:12 8:45:30").
        $dateTime = DateTime::createFromFormat(
            'Y:m:d H:i:s',
            $value,
        );
        $errors = DateTime::getLastErrors();

        if (
            false === $dateTime
            || (
                false !== $errors
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            return null;
        }

        return $dateTime;
    }

    private static function iso(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_numeric($value)
            ? intval($value)
            : null;
    }

    /**
     * A rational EXIF value ("50/1", "1/250") or a plain number, as a float.
     */
    private static function rational(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return floatval($value);
        }

        if (!is_string($value)) {
            return null;
        }

        $parts = explode(
            '/',
            $value,
            2,
        );
        if (
            2 !== count($parts)
            || !is_numeric($parts[0])
            || !is_numeric($parts[1])
            || 0.0 === floatval($parts[1])
        ) {
            return null;
        }

        return floatval($parts[0]) / floatval($parts[1]);
    }

    private static function shutterSpeed(?float $exposureTime): ?string
    {
        if (
            null === $exposureTime
            || $exposureTime <= 0.0
        ) {
            return null;
        }

        return $exposureTime < 1.0
            ? sprintf(
                '1/%d s',
                intval(round(1.0 / $exposureTime)),
            )
            : sprintf(
                '%s s',
                self::number($exposureTime),
            );
    }

    private static function aperture(?float $fNumber): ?string
    {
        // Guard against garbage like an "0/1" f-number, which some phones write for "unknown"; matches shutterSpeed().
        if (
            null === $fNumber
            || $fNumber <= 0.0
        ) {
            return null;
        }

        return sprintf(
            'f/%s',
            self::number($fNumber),
        );
    }

    /**
     * A rational kept only when strictly positive: a "0/1" focal length is a placeholder some cameras write for
     * "unknown", not a real 0 mm lens.
     */
    private static function positiveFloat(?float $value): ?float
    {
        if (
            null === $value
            || $value <= 0.0
        ) {
            return null;
        }

        return $value;
    }

    /**
     * @param mixed $parts the degree/minute/second rationals, e.g. ["52/1", "4/1", "1234/100"]
     */
    private static function gps(
        mixed $parts,
        mixed $ref,
        string $negativeRef,
    ): ?float {
        if (
            !is_array($parts)
            || 3 !== count($parts)
        ) {
            return null;
        }

        $degrees = self::rational($parts[0] ?? null);
        $minutes = self::rational($parts[1] ?? null);
        $seconds = self::rational($parts[2] ?? null);
        if (
            null === $degrees
            || null === $minutes
            || null === $seconds
        ) {
            return null;
        }

        $decimal = $degrees + $minutes / 60.0 + $seconds / 3600.0;

        return self::string($ref) === $negativeRef
            ? -$decimal
            : $decimal;
    }

    private static function number(float $value): string
    {
        return rtrim(
            rtrim(
                sprintf(
                    '%.1f',
                    $value,
                ),
                '0',
            ),
            '.',
        );
    }
}
