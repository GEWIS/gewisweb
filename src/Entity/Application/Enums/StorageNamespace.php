<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use InvalidArgumentException;

use function in_array;
use function sprintf;

/**
 * The domains that {@see \App\Service\Application\FileStorage} stores files for. Each case maps a domain onto its
 * directory prefix under the storage root (`data/`), the whitelist of MIME types and the maximum file size accepted
 * for it, and whether it is private (needs an authenticated, signed request to serve). Photo and company namespaces are
 * scoped per owning entity (album or company), which also keeps their directories bounded and means the same bytes in
 * two albums never share one stored file.
 *
 * The backing values are stable machine keys, never part of a URL; the URL always carries the full stored path.
 */
enum StorageNamespace: string
{
    /** Album photo originals: the only private namespace, scoped per album. */
    case PhotoOriginal = 'photo-original';

    /** Generated 2x2 album cover mosaics, scoped per album (public). */
    case PhotoCover = 'photo-cover';

    /** The current photo of the week, copied out of its album so the anonymous frontpage can serve it (public). */
    case PhotoWeekly = 'photo-weekly';

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
     * Scoped namespaces (photos per album, company assets per company) require a non-empty $scope; the others reject
     * one.
     */
    public function directory(?string $scope = null): string
    {
        return match ($this) {
            self::PhotoOriginal => sprintf(
                'photos/albums/%s',
                $this->requireScope($scope),
            ),
            self::PhotoCover => sprintf(
                'photos/covers/%s',
                $this->requireScope($scope),
            ),
            self::PhotoWeekly => $this->rejectScope(
                $scope,
                'photos/weekly',
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
     * Whether {@see directory()} needs a scope: photos are scoped per album, company assets per company.
     */
    public function requiresScope(): bool
    {
        return match ($this) {
            self::PhotoOriginal, self::PhotoCover, self::CompanyImage, self::CompanyAttachment => true,
            default => false,
        };
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
                'image/avif',
                'image/heic',
                'image/heif',
            ],
        };
    }

    /**
     * The maximum size, in bytes, accepted for a file in this namespace.
     */
    public function maxFileSizeBytes(): int
    {
        return 32 * self::MEGABYTE;
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
