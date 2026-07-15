<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

/**
 * The variant map: which {@see ImageVariant}s (and at what WebP quality) to pre-generate for a given consumer role.
 *
 * A role is finer-grained than a {@see StorageNamespace} on purpose. Company logos and banners share the
 * `career/{id}/images` namespace but need different renditions (small near-lossless logos versus large photographic
 * banners), which a namespace alone cannot express. A future consumer (e.g. an og:image) would be one more case here.
 */
enum ImageProfile: string
{
    /** Album photo originals: the full width-fit ladder for grid, lightbox and deep zoom. */
    case AlbumPhoto = 'album-photo';

    /** Generated album cover mosaics: the cover crops. */
    case AlbumCover = 'album-cover';

    /** Organ images: square thumbnail and cover crops, plus a page-width fit. */
    case OrganImage = 'organ-image';

    /** Company logos: small renditions at near-lossless quality (flat colour/text smears under lossy). */
    case CompanyLogo = 'company-logo';

    /** Company banner-package images: large photographic renditions. */
    case CompanyBanner = 'company-banner';

    /** Images embedded in custom pages/markdown. */
    case PageImage = 'page-image';

    /**
     * Default WebP quality for photographic content. The Sprint-1 A/B test may retune it (80 to 90).
     */
    private const int PHOTO_QUALITY = 85;

    /** Near-lossless quality for flat colour / text (true lossless awaits the vips-driver encoder). */
    private const int FLAT_QUALITY = 100;

    /**
     * The variants to pre-generate for this profile.
     *
     * @return non-empty-list<ImageVariant>
     */
    public function variants(): array
    {
        return match ($this) {
            self::AlbumPhoto => [
                ImageVariant::W320,
                ImageVariant::W640,
                ImageVariant::W960,
                ImageVariant::W1280,
                ImageVariant::W1920,
                ImageVariant::W2560,
            ],
            self::AlbumCover => [
                ImageVariant::Cover,
                ImageVariant::Cover2x,
            ],
            self::OrganImage => [
                ImageVariant::Square,
                ImageVariant::Square2x,
                ImageVariant::Cover,
                ImageVariant::Cover2x,
                ImageVariant::W960,
            ],
            self::CompanyLogo => [
                ImageVariant::W320,
                ImageVariant::W640,
            ],
            self::CompanyBanner => [
                ImageVariant::W1280,
                ImageVariant::W2560,
            ],
            self::PageImage => [
                ImageVariant::W320,
                ImageVariant::W640,
                ImageVariant::W960,
                ImageVariant::W1280,
                ImageVariant::W1920,
            ],
        };
    }

    /**
     * The WebP quality to encode this profile's variants at.
     */
    public function webpQuality(): int
    {
        return match ($this) {
            self::CompanyLogo => self::FLAT_QUALITY,
            default => self::PHOTO_QUALITY,
        };
    }
}
