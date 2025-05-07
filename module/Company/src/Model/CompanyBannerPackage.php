<?php

declare(strict_types=1);

namespace Company\Model;

use Company\Model\Enums\CompanyPackageTypes;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;

/**
 * CompanyBannerPackage model.
 */
#[Entity]
class CompanyBannerPackage extends CompanyPackage
{
    /**
     * The banner's image URL.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $image = null;

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
