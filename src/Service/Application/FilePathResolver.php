<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\StorageNamespace;

use function str_contains;
use function str_starts_with;

/**
 * Maps a stored source path back onto the {@see StorageNamespace} it belongs to (which decides whether serving it needs
 * a signature and session) and, for the image namespaces, together with the requested variant onto the
 * {@see ImageProfile} that governs its encoding, so both the serving gate and the synchronous generate-on-miss encode
 * agree on the same rules. Not every namespace holds images: attachments and meeting files are PDFs, which resolve to a
 * namespace but never to a profile.
 */
final readonly class FilePathResolver
{
    /**
     * The namespace a stored path belongs to, or null if it matches no known namespace.
     */
    public function namespaceForPath(string $path): ?StorageNamespace
    {
        return match (true) {
            str_starts_with(
                $path,
                'photos/albums/',
            ) => StorageNamespace::PhotoOriginal,
            str_starts_with(
                $path,
                'photos/covers/',
            ) => StorageNamespace::PhotoCover,
            str_starts_with(
                $path,
                'photos/weekly/',
            ) => StorageNamespace::PhotoWeekly,
            str_starts_with(
                $path,
                'organs/images/',
            ) => StorageNamespace::OrganImage,
            str_starts_with(
                $path,
                'pages/images/',
            ) => StorageNamespace::PageImage,
            str_starts_with(
                $path,
                'career/',
            ) && str_contains(
                $path,
                '/images/',
            ) => StorageNamespace::CompanyImage,
            str_starts_with(
                $path,
                'career/',
            ) && str_contains(
                $path,
                '/attachments/',
            ) => StorageNamespace::CompanyAttachment,
            str_starts_with(
                $path,
                'meetings/reference/',
            ) => StorageNamespace::ReferenceDocument,
            str_starts_with(
                $path,
                'meetings/',
            ) && str_contains(
                $path,
                '/documents/',
            ) => StorageNamespace::MeetingDocument,
            str_starts_with(
                $path,
                'meetings/',
            ) && str_contains(
                $path,
                '/minutes/',
            ) => StorageNamespace::MeetingMinutes,
            str_starts_with(
                $path,
                'education/courses/',
            ) => StorageNamespace::EducationDocument,
            str_starts_with(
                $path,
                'education/pages/',
            ) => StorageNamespace::EducationDocumentPage,
            str_starts_with(
                $path,
                'education/downloads/',
            ) => StorageNamespace::EducationDownload,
            default => null,
        };
    }

    /**
     * The image profile (variant set + quality) that governs a variant of a stored path, or null when the path is not a
     * variant-generating image namespace. Company logos and banners share the career namespace, so the requested
     * variant says which of the two is being served: a banner is only ever asked for at one of the boxes its format
     * is shown in.
     */
    public function profileForPath(
        string $path,
        ImageVariant $variant,
    ): ?ImageProfile {
        return match ($this->namespaceForPath($path)) {
            StorageNamespace::PhotoOriginal, StorageNamespace::PhotoWeekly => ImageProfile::AlbumPhoto,
            StorageNamespace::PhotoCover => ImageProfile::AlbumCover,
            StorageNamespace::OrganImage => ImageProfile::OrganImage,
            StorageNamespace::PageImage => ImageProfile::PageImage,
            StorageNamespace::CompanyImage => match ($variant) {
                ImageVariant::Leaderboard, ImageVariant::Leaderboard2x => ImageProfile::CompanyBannerLeaderboard,
                ImageVariant::Billboard, ImageVariant::Billboard2x => ImageProfile::CompanyBannerBillboard,
                default => ImageProfile::CompanyLogo,
            },
            default => null,
        };
    }
}
