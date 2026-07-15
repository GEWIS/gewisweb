<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Photo\Photo;
use DateTime;

use function array_key_exists;
use function count;
use function explode;
use function is_array;
use function is_numeric;
use function is_string;
use function round;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * Camera metadata read from a photo's EXIF, mapped onto the columns of {@see Photo}. Every field is optional: a photo
 * without EXIF (or in a format that carries none) yields an all-null instance.
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
            ) ? 1 === ((int) $exif['Flash'] & 1) : null,
            focalLength: self::rational($exif['FocalLength'] ?? null),
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

        // The model often already includes the make (e.g. "Canon EOS 60D"); only prefix it when it does not.
        if (
            null === $make
            || str_starts_with(
                $model,
                $make,
            )
        ) {
            return $model;
        }

        return $make . ' ' . $model;
    }

    private static function dateTime(mixed $value): ?DateTime
    {
        $value = self::string($value);
        if (null === $value) {
            return null;
        }

        // createFromFormat rolls invalid components over rather than failing (the "0000:00:00 00:00:00" no-date
        // sentinel would become a real date), so require the parse to round-trip.
        $dateTime = DateTime::createFromFormat(
            'Y:m:d H:i:s',
            $value,
        );

        return false !== $dateTime && $dateTime->format('Y:m:d H:i:s') === $value
            ? $dateTime
            : null;
    }

    private static function iso(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_numeric($value)
            ? (int) $value
            : null;
    }

    /**
     * A rational EXIF value ("50/1", "1/250") or a plain number, as a float.
     */
    private static function rational(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
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
            || 0.0 === (float) $parts[1]
        ) {
            return null;
        }

        return (float) $parts[0] / (float) $parts[1];
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
                (int) round(1.0 / $exposureTime),
            )
            : sprintf(
                '%s s',
                self::number($exposureTime),
            );
    }

    private static function aperture(?float $fNumber): ?string
    {
        return null === $fNumber
            ? null
            : sprintf(
                'f/%s',
                self::number($fNumber),
            );
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
