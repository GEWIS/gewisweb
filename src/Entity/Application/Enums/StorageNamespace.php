<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use InvalidArgumentException;

use function in_array;
use function sprintf;

/**
 * The domains that {@see \App\Service\Application\FileStorage} stores files for. Each case maps a domain onto its
 * directory prefix under the storage root (`data/`), the whitelist of MIME types and the maximum file size accepted
 * for it, and whether it is private (needs an authenticated, signed request to serve) or sharded (originals are spread
 * over 256 two-character subdirectories so no single directory grows unbounded).
 *
 * The backing values are stable machine keys, never part of a URL; the URL always carries the full stored path.
 */
enum StorageNamespace: string
{
    /** Album photo originals: the only private namespace, sharded by the first two characters of the hash. */
    case PhotoOriginal = 'photo-original';

    /** Generated 2x2 album cover mosaics (public). */
    case PhotoCover = 'photo-cover';

    /** Company logos and banner-package images, scoped per company (public). */
    case CompanyImage = 'company-image';

    /** Vacancy attachments (PDFs), scoped per company (public). */
    case CompanyAttachment = 'company-attachment';

    /** Organ cover and thumbnail images (public). */
    case OrganImage = 'organ-image';

    /** Images embedded in custom pages/markdown (public). */
    case PageImage = 'page-image';

    private const int MEGABYTE = 1024 * 1024;

    /**
     * The directory prefix (no leading or trailing slash) this namespace stores into, relative to the storage root.
     * Scoped namespaces (per-company) require a non-empty $scope; the others reject one.
     */
    public function directory(?string $scope = null): string
    {
        return match ($this) {
            self::PhotoOriginal => $this->rejectScope(
                $scope,
                'photos/albums',
            ),
            self::PhotoCover => $this->rejectScope(
                $scope,
                'photos/covers',
            ),
            self::OrganImage => $this->rejectScope(
                $scope,
                'organs/images',
            ),
            self::PageImage => $this->rejectScope(
                $scope,
                'pages/images',
            ),
            self::CompanyImage => sprintf(
                'career/%s/images',
                $this->requireScope($scope),
            ),
            self::CompanyAttachment => sprintf(
                'career/%s/attachments',
                $this->requireScope($scope),
            ),
        };
    }

    /**
     * Whether originals in this namespace are spread over two-character hash-prefix subdirectories. Only the album
     * originals warrant it; every other namespace holds comparatively few files.
     */
    public function isSharded(): bool
    {
        return self::PhotoOriginal === $this;
    }

    /**
     * Whether serving a file from this namespace requires an authenticated, signature-validated request. Only album
     * originals (and, by extension, their generated variants) are member-only; covers, career, organ and page assets
     * are public and immutably cacheable.
     */
    public function isPrivate(): bool
    {
        return self::PhotoOriginal === $this;
    }

    /**
     * The MIME types accepted for this namespace. Attachments are PDFs; everything else is a small set of raster image
     * formats (SVG is deliberately excluded because it can carry active content).
     *
     * @return non-empty-list<string>
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::CompanyAttachment => ['application/pdf'],
            default => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ],
        };
    }

    /**
     * The maximum size, in bytes, accepted for a file in this namespace.
     */
    public function maxFileSizeBytes(): int
    {
        return 64 * self::MEGABYTE;
    }

    /**
     * Whether the given MIME type is accepted for this namespace.
     */
    public function acceptsMimeType(string $mimeType): bool
    {
        return in_array(
            $mimeType,
            $this->allowedMimeTypes(),
            true,
        );
    }

    private function requireScope(?string $scope): string
    {
        if (
            null === $scope
            || '' === $scope
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Storage namespace "%s" requires a scope.',
                    $this->value,
                ),
            );
        }

        return $scope;
    }

    private function rejectScope(
        ?string $scope,
        string $directory,
    ): string {
        if (null !== $scope) {
            throw new InvalidArgumentException(
                sprintf(
                    'Storage namespace "%s" does not accept a scope.',
                    $this->value,
                ),
            );
        }

        return $directory;
    }
}
