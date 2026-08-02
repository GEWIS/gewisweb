<?php

declare(strict_types=1);

namespace App\Entity\Career\Enums;

use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\CompanyPackage;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Enum for the different types of company packages that exist.
 */
enum CompanyPackageTypes: string
{
    case Banner = 'banner';
    case Featured = 'featured';
    case Highlight = 'highlight';
    case Job = 'job';

    /**
     * The entity a package of this type is stored as. The packages share one table and differ by subclass, so
     * anything that creates or queries one kind has to name that subclass.
     *
     * @return class-string<CompanyPackage>
     * @psalm-return (
     *     $type is CompanyPackageTypes::Banner
     *     ? class-string<CompanyBannerPackage>
     *     : (
     *         $type is CompanyPackageTypes::Featured
     *         ? class-string<CompanyFeaturedPackage>
     *         : (
     *             $type is CompanyPackageTypes::Highlight
     *             ? class-string<CompanyHighlightPackage>
     *             : class-string<CompanyJobPackage>
     *         )
     *     )
     * )
     */
    public static function entityClass(self $type): string
    {
        return match ($type) {
            self::Banner => CompanyBannerPackage::class,
            self::Featured => CompanyFeaturedPackage::class,
            self::Highlight => CompanyHighlightPackage::class,
            self::Job => CompanyJobPackage::class,
        };
    }

    /**
     * What the package is called where somebody has to pick or recognise one.
     */
    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Banner => new TranslatableMessage('Banner'),
            self::Featured => new TranslatableMessage('Featured'),
            self::Highlight => new TranslatableMessage('Highlight'),
            self::Job => new TranslatableMessage('Jobs'),
        };
    }
}
