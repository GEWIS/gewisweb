<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\StorageNamespace;
use App\Service\Application\FilePathResolver;
use PHPUnit\Framework\TestCase;

/**
 * The resolver maps a stored path back onto its namespace (which decides whether serving needs a signature) and, with
 * the variant, onto its encoding profile. The tricky case is the shared career namespace, which splits into logo vs.
 * banner purely by the requested variant's width. PDF namespaces resolve to a namespace but never to a profile.
 */
final class FilePathResolverTest extends TestCase
{
    public function testMapsEachNamespacePrefix(): void
    {
        $resolver = new FilePathResolver();

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
        self::assertSame(
            StorageNamespace::MeetingDocument,
            $resolver->namespaceForPath('meetings/documents/x.pdf'),
        );
        self::assertSame(
            StorageNamespace::MeetingMinutes,
            $resolver->namespaceForPath('meetings/minutes/x.pdf'),
        );
        self::assertSame(
            StorageNamespace::ReferenceDocument,
            $resolver->namespaceForPath('meetings/reference/x.pdf'),
        );
    }

    public function testUnknownPathHasNoNamespace(): void
    {
        self::assertNull(new FilePathResolver()->namespaceForPath('something/else/x.jpg'));
    }

    public function testAlbumPhotosCoversAndMeetingFilesArePrivate(): void
    {
        // A cover is a mosaic of members-only photos, so it is private too; the weekly copy and career assets are not.
        self::assertTrue(StorageNamespace::PhotoOriginal->isPrivate());
        self::assertTrue(StorageNamespace::PhotoCover->isPrivate());
        self::assertTrue(StorageNamespace::MeetingDocument->isPrivate());
        self::assertTrue(StorageNamespace::MeetingMinutes->isPrivate());
        self::assertTrue(StorageNamespace::ReferenceDocument->isPrivate());
        self::assertFalse(StorageNamespace::PhotoWeekly->isPrivate());
        self::assertFalse(StorageNamespace::CompanyImage->isPrivate());
    }

    public function testCareerImagesSplitIntoLogoAndBannerByVariantWidth(): void
    {
        $resolver = new FilePathResolver();
        $path = 'career/42/images/x.png';

        // A banner is asked for at one of its own two boxes; everything else on a company is the logo.
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
            ImageProfile::CompanyBannerLeaderboard,
            $resolver->profileForPath(
                $path,
                ImageVariant::Leaderboard2x,
            ),
        );
        self::assertSame(
            ImageProfile::CompanyBannerBillboard,
            $resolver->profileForPath(
                $path,
                ImageVariant::Billboard,
            ),
        );
    }

    public function testPhotoAndOrganProfilesResolve(): void
    {
        $resolver = new FilePathResolver();

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
        self::assertNull($resolver->profileForPath('meetings/documents/x.pdf', ImageVariant::W320));
    }
}
