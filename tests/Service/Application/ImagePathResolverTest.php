<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\StorageNamespace;
use App\Service\Application\ImagePathResolver;
use PHPUnit\Framework\TestCase;

/**
 * The resolver maps a stored path back onto its namespace (which decides whether serving needs a signature) and, with
 * the variant, onto its encoding profile. The tricky case is the shared career namespace, which splits into logo vs.
 * banner purely by the requested variant's width.
 */
final class ImagePathResolverTest extends TestCase
{
    public function testMapsEachNamespacePrefix(): void
    {
        $resolver = new ImagePathResolver();

        self::assertSame(
            StorageNamespace::PhotoOriginal,
            $resolver->namespaceForPath('photos/albums/ab/x.jpg'),
        );
        self::assertSame(
            StorageNamespace::PhotoCover,
            $resolver->namespaceForPath('photos/covers/x.webp'),
        );
        self::assertSame(
            StorageNamespace::OrganImage,
            $resolver->namespaceForPath('organs/images/x.jpg'),
        );
        self::assertSame(
            StorageNamespace::PageImage,
            $resolver->namespaceForPath('pages/images/x.jpg'),
        );
        self::assertSame(
            StorageNamespace::CompanyImage,
            $resolver->namespaceForPath('career/42/images/x.png'),
        );
        self::assertSame(
            StorageNamespace::CompanyAttachment,
            $resolver->namespaceForPath('career/42/attachments/x.pdf'),
        );
    }

    public function testUnknownPathHasNoNamespace(): void
    {
        self::assertNull(new ImagePathResolver()->namespaceForPath('something/else/x.jpg'));
    }

    public function testOnlyTheAlbumOriginalsNamespaceIsPrivate(): void
    {
        self::assertTrue(StorageNamespace::PhotoOriginal->isPrivate());
        self::assertFalse(StorageNamespace::PhotoCover->isPrivate());
        self::assertFalse(StorageNamespace::CompanyImage->isPrivate());
    }

    public function testCareerImagesSplitIntoLogoAndBannerByVariantWidth(): void
    {
        $resolver = new ImagePathResolver();
        $path = 'career/42/images/x.png';

        // Small variants are the logo profile (near-lossless); large ones the banner profile.
        self::assertSame(
            ImageProfile::CompanyLogo,
            $resolver->profileForPath(
                $path,
                ImageVariant::W320,
            ),
        );
        self::assertSame(
            ImageProfile::CompanyLogo,
            $resolver->profileForPath(
                $path,
                ImageVariant::W640,
            ),
        );
        self::assertSame(
            ImageProfile::CompanyBanner,
            $resolver->profileForPath(
                $path,
                ImageVariant::W1280,
            ),
        );
        self::assertSame(
            ImageProfile::CompanyBanner,
            $resolver->profileForPath(
                $path,
                ImageVariant::W2560,
            ),
        );
    }

    public function testPhotoAndOrganProfilesResolve(): void
    {
        $resolver = new ImagePathResolver();

        self::assertSame(
            ImageProfile::AlbumPhoto,
            $resolver->profileForPath(
                'photos/albums/ab/x.jpg',
                ImageVariant::W320,
            ),
        );
        self::assertSame(
            ImageProfile::OrganImage,
            $resolver->profileForPath(
                'organs/images/x.jpg',
                ImageVariant::Square,
            ),
        );
        self::assertNull($resolver->profileForPath('unknown/x.jpg', ImageVariant::W320));
    }
}
