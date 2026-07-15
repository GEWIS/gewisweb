<?php

declare(strict_types=1);

namespace App\Tests\Service\Photo;

use App\Entity\Photo\Photo;
use App\Service\Photo\PhotoMetadata;
use DateTime;
use PHPUnit\Framework\TestCase;

final class PhotoMetadataTest extends TestCase
{
    public function testEmptyExifYieldsAllNull(): void
    {
        $metadata = PhotoMetadata::fromExif([]);

        self::assertNull($metadata->dateTime);
        self::assertNull($metadata->artist);
        self::assertNull($metadata->camera);
        self::assertNull($metadata->flash);
        self::assertNull($metadata->focalLength);
        self::assertNull($metadata->exposureTime);
        self::assertNull($metadata->shutterSpeed);
        self::assertNull($metadata->aperture);
        self::assertNull($metadata->iso);
        self::assertNull($metadata->latitude);
        self::assertNull($metadata->longitude);
    }

    public function testCameraDropsMakeAlreadyInModel(): void
    {
        self::assertSame(
            'Canon EOS 60D',
            PhotoMetadata::fromExif(['Make' => 'Canon', 'Model' => 'Canon EOS 60D'])->camera,
        );
    }

    public function testCameraPrefixesMakeWhenModelOmitsIt(): void
    {
        self::assertSame(
            'NIKON D3500',
            PhotoMetadata::fromExif(['Make' => 'NIKON', 'Model' => 'D3500'])->camera,
        );
    }

    public function testCameraFallsBackToMakeAlone(): void
    {
        self::assertSame(
            'Apple',
            PhotoMetadata::fromExif(['Make' => 'Apple'])->camera,
        );
    }

    public function testDateTimePrefersDateTimeOriginal(): void
    {
        $metadata = PhotoMetadata::fromExif([
            'DateTimeOriginal' => '2019:08:12 18:45:30',
            'DateTime' => '2020:01:01 00:00:00',
        ]);

        self::assertInstanceOf(
            DateTime::class,
            $metadata->dateTime,
        );
        self::assertSame(
            '2019-08-12 18:45:30',
            $metadata->dateTime->format('Y-m-d H:i:s'),
        );
    }

    public function testInvalidDateTimeIsNull(): void
    {
        self::assertNull(PhotoMetadata::fromExif(['DateTimeOriginal' => '0000:00:00 00:00:00'])->dateTime);
        self::assertNull(PhotoMetadata::fromExif(['DateTimeOriginal' => 'not a date'])->dateTime);
    }

    public function testIsoAcceptsIntStringAndArray(): void
    {
        self::assertSame(
            100,
            PhotoMetadata::fromExif(['ISOSpeedRatings' => 100])->iso,
        );
        self::assertSame(
            400,
            PhotoMetadata::fromExif(['ISOSpeedRatings' => '400'])->iso,
        );
        self::assertSame(
            200,
            PhotoMetadata::fromExif(['ISOSpeedRatings' => [200, 200]])->iso,
        );
    }

    public function testFocalLengthAndExposureRationals(): void
    {
        $metadata = PhotoMetadata::fromExif(['FocalLength' => '50/1', 'ExposureTime' => '1/250']);

        self::assertEqualsWithDelta(
            50.0,
            $metadata->focalLength,
            1e-9,
        );
        self::assertEqualsWithDelta(
            0.004,
            $metadata->exposureTime,
            1e-9,
        );
    }

    public function testShutterSpeedFormatsFastAndSlow(): void
    {
        self::assertSame(
            '1/250 s',
            PhotoMetadata::fromExif(['ExposureTime' => '1/250'])->shutterSpeed,
        );
        self::assertSame(
            '2 s',
            PhotoMetadata::fromExif(['ExposureTime' => '2/1'])->shutterSpeed,
        );
        self::assertNull(PhotoMetadata::fromExif(['ExposureTime' => '0/1'])->shutterSpeed);
    }

    public function testApertureFormatting(): void
    {
        self::assertSame(
            'f/2.8',
            PhotoMetadata::fromExif(['FNumber' => '28/10'])->aperture,
        );
        self::assertSame(
            'f/8',
            PhotoMetadata::fromExif(['FNumber' => '80/10'])->aperture,
        );
    }

    public function testFlashBit(): void
    {
        self::assertFalse(PhotoMetadata::fromExif(['Flash' => 0])->flash);
        self::assertTrue(PhotoMetadata::fromExif(['Flash' => 1])->flash);
        self::assertFalse(PhotoMetadata::fromExif(['Flash' => 16])->flash);
        self::assertTrue(PhotoMetadata::fromExif(['Flash' => 25])->flash);
        self::assertNull(PhotoMetadata::fromExif([])->flash);
    }

    public function testGpsToSignedDecimal(): void
    {
        $north = PhotoMetadata::fromExif([
            'GPSLatitude' => [
                '52/1',
                '4/1',
                '3000/100',
            ],
            'GPSLatitudeRef' => 'N',
            'GPSLongitude' => [
                '4/1',
                '30/1',
                '0/1',
            ],
            'GPSLongitudeRef' => 'E',
        ]);
        self::assertEqualsWithDelta(
            52.075,
            $north->latitude,
            1e-9,
        );
        self::assertEqualsWithDelta(
            4.5,
            $north->longitude,
            1e-9,
        );

        $south = PhotoMetadata::fromExif([
            'GPSLatitude' => [
                '52/1',
                '4/1',
                '3000/100',
            ],
            'GPSLatitudeRef' => 'S',
            'GPSLongitude' => [
                '4/1',
                '30/1',
                '0/1',
            ],
            'GPSLongitudeRef' => 'W',
        ]);
        self::assertEqualsWithDelta(
            -52.075,
            $south->latitude,
            1e-9,
        );
        self::assertEqualsWithDelta(
            -4.5,
            $south->longitude,
            1e-9,
        );
    }

    public function testIncompleteGpsIsNull(): void
    {
        $metadata = PhotoMetadata::fromExif(['GPSLatitude' => ['52/1', '4/1'], 'GPSLatitudeRef' => 'N']);

        self::assertNull($metadata->latitude);
    }

    public function testApplyToOverridesDateTimeOnlyWhenPresent(): void
    {
        $photo = new Photo();
        $original = new DateTime('2021-05-05 05:05:05');
        $photo->setDateTime($original);

        PhotoMetadata::empty()->applyTo($photo);
        self::assertSame(
            $original,
            $photo->getDateTime(),
        );

        PhotoMetadata::fromExif([
            'DateTimeOriginal' => '2019:08:12 18:45:30',
            'Make' => 'Canon',
            'Model' => 'Canon EOS 60D',
            'ISOSpeedRatings' => 200,
        ])->applyTo($photo);

        self::assertSame(
            '2019-08-12 18:45:30',
            $photo->getDateTime()->format('Y-m-d H:i:s'),
        );
        self::assertSame(
            'Canon EOS 60D',
            $photo->getCamera(),
        );
        self::assertSame(
            200,
            $photo->getIso(),
        );
    }
}
