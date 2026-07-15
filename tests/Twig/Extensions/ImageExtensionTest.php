<?php

declare(strict_types=1);

namespace App\Tests\Twig\Extensions;

use App\Entity\Application\Enums\ImageVariant;
use App\Service\Application\ImagePathResolver;
use App\Service\Application\ImageSigner;
use App\Service\Application\ImageUrlBuilder;
use App\Twig\Extensions\ImageExtension;
use PHPUnit\Framework\TestCase;

use function str_contains;
use function str_starts_with;

/**
 * The Twig helpers build serving URLs: private (album) URLs are day-signed, public ones (covers) are left bare, and a
 * srcset carries each variant's width descriptor.
 */
final class ImageExtensionTest extends TestCase
{
    public function testPublicImageUrlIsUnsigned(): void
    {
        // The weekly photo copy is public (covers are now members-only, hence signed).
        $url = $this->extension()->imageUrl(
            'photos/weekly/abc.webp',
            ImageVariant::W320,
        );

        self::assertSame(
            '/img/w320/photos/weekly/abc.webp',
            $url,
        );
    }

    public function testPrivateImageUrlIsSigned(): void
    {
        $url = $this->extension()->imageUrl(
            'photos/albums/ab/abc.jpg',
            'w320',
        );

        self::assertTrue(str_starts_with($url, '/img/w320/photos/albums/ab/abc.jpg?'));
        self::assertTrue(str_contains($url, 'expires='));
        self::assertTrue(str_contains($url, 'signature='));
    }

    public function testSrcsetCarriesWidthDescriptors(): void
    {
        $srcset = $this->extension()->imageSrcset(
            'photos/weekly/abc.webp',
            [
                ImageVariant::W320,
                ImageVariant::W640,
            ],
        );

        self::assertSame(
            '/img/w320/photos/weekly/abc.webp 320w, /img/w640/photos/weekly/abc.webp 640w',
            $srcset,
        );
    }

    private function extension(): ImageExtension
    {
        return new ImageExtension(
            new ImageUrlBuilder(
                new ImageSigner('test-key'),
                new ImagePathResolver(),
            ),
        );
    }
}
