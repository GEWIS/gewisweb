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
 * a signature and session) and, together with the requested variant, onto the {@see ImageProfile} that governs its
 * encoding, so both the serving gate and the synchronous generate-on-miss encode agree on the same rules.
 */
final readonly class ImagePathResolver
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
            default => null,
        };
    }

    /**
     * The image profile (variant set + quality) that governs a variant of a stored path, or null when the path is not a
     * variant-generating image namespace. Company images split into logo vs. banner by the requested variant's width,
     * since both share the same career namespace.
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
            StorageNamespace::CompanyImage => $variant->width() <= ImageVariant::W640->width()
                ? ImageProfile::CompanyLogo
                : ImageProfile::CompanyBanner,
            default => null,
        };
    }
}
