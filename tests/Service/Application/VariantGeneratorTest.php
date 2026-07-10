<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\StorageNamespace;
use App\Service\Application\FileStorage;
use App\Service\Application\ImageManagerProvider;
use App\Service\Application\VariantGenerator;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

use function dirname;
use function getimagesizefromstring;

use const IMAGETYPE_WEBP;

/**
 * Verifies the variant pipeline end-to-end on the GD driver (always present) against an in-memory storage: variants are
 * WebP, width-fit variants scale to the requested width, the no-upscale rule skips variants wider than the original,
 * and fixed-crop variants land on their exact box. The committed fixture images (800x600 landscape, 500x500 square)
 * provide known dimensions.
 */
final class VariantGeneratorTest extends TestCase
{
    private const string LANDSCAPE_FIXTURE = 'gala-dinner-1.jpg'; // 800x600
    private const string SQUARE_FIXTURE = 'trip-1.jpg';           // 500x500

    public function testGeneratesDownscaledWebpForAWidthFitVariant(): void
    {
        [
            $storage, $generator
        ] = $this->pipeline();
        $source = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->fixture(self::LANDSCAPE_FIXTURE),
            '1',
        )->path;

        self::assertTrue($generator->generateVariant($source, ImageVariant::W320, 85));

        [
            $width,, $type
        ] = $this->imageInfo(
            $storage,
            $generator->cachePath(
                $source,
                ImageVariant::W320,
            ),
        );
        self::assertSame(
            320,
            $width,
        );
        self::assertSame(
            IMAGETYPE_WEBP,
            $type,
        );
    }

    public function testNeverUpscalesAWidthFitVariant(): void
    {
        [
            $storage, $generator
        ] = $this->pipeline();
        // The landscape fixture is 800 wide, so a 960-wide variant would be an upscale and must be skipped.
        $source = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->fixture(self::LANDSCAPE_FIXTURE),
            '1',
        )->path;

        self::assertFalse($generator->generateVariant($source, ImageVariant::W960, 85));
        self::assertFalse($generator->variantExists($source, ImageVariant::W960));
    }

    public function testFixedCropVariantLandsOnItsExactBox(): void
    {
        [
            $storage, $generator
        ] = $this->pipeline();
        // A square source cover-cropped to the landscape cover box must come out exactly 640x360.
        $source = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->fixture(self::SQUARE_FIXTURE),
            '1',
        )->path;

        self::assertTrue($generator->generateVariant($source, ImageVariant::Cover, 85));

        [
            $width, $height
        ] = $this->imageInfo(
            $storage,
            $generator->cachePath(
                $source,
                ImageVariant::Cover,
            ),
        );
        self::assertSame(
            640,
            $width,
        );
        self::assertSame(
            360,
            $height,
        );
    }

    public function testGenerateProfileProducesFittingVariantsAndSkipsUpscales(): void
    {
        [
            $storage, $generator
        ] = $this->pipeline();
        $source = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->fixture(self::LANDSCAPE_FIXTURE),
            '1',
        )->path;

        $generator->generate(
            $source,
            ImageProfile::AlbumPhoto,
        );

        // 800px wide original: w320 and w640 fit, everything above is an upscale and is skipped.
        self::assertTrue($generator->variantExists($source, ImageVariant::W320));
        self::assertTrue($generator->variantExists($source, ImageVariant::W640));
        self::assertFalse($generator->variantExists($source, ImageVariant::W960));
        self::assertFalse($generator->variantExists($source, ImageVariant::W2560));
    }

    public function testCachePathMirrorsTheSourceUnderTheVariantDirectory(): void
    {
        [, $generator
        ] = $this->pipeline();

        self::assertSame(
            'cache/images/w320/photos/albums/ab/deadbeef.webp',
            $generator->cachePath(
                'photos/albums/ab/deadbeef.jpg',
                ImageVariant::W320,
            ),
        );
    }

    /**
     * @return array{FileStorage, VariantGenerator}
     */
    private function pipeline(): array
    {
        $storage = new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));

        return [
            $storage,
            new VariantGenerator(
                $storage,
                new ImageManagerProvider(),
            ),
        ];
    }

    private function fixture(string $name): string
    {
        return dirname(
            __DIR__,
            3,
        ) . '/tests/Resources/images/' . $name;
    }

    /**
     * @return array{int, int, int}
     */
    private function imageInfo(
        FileStorage $storage,
        string $path,
    ): array {
        self::assertTrue($storage->exists($path));
        $info = getimagesizefromstring($storage->read($path));
        self::assertIsArray($info);

        return [
            $info[0],
            $info[1],
            $info[2],
        ];
    }
}
