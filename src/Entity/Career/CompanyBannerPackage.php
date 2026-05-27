<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Repository\Career\CompanyBannerPackageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;

/**
 * CompanyBannerPackage model.
 */
#[Entity(repositoryClass: CompanyBannerPackageRepository::class)]
class CompanyBannerPackage extends CompanyPackage
{
    /**
     * The banner's image URL.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $image = null;

    /**
     * Get the banner's image URL.
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the banner's image URL.
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    #[Override]
    public function getType(): CompanyPackageTypes
    {
        return CompanyPackageTypes::Banner;
    }
}
