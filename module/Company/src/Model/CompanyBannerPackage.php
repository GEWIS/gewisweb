<?php

declare(strict_types=1);

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};
use Company\Model\Enums\CompanyPackageTypes;

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
        type: "string",
        nullable: true,
    )]
    protected ?string $image = null;

    /**
     * Get the banner's image URL.
     *
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the banner's image URL.
     *
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): CompanyPackageTypes
    {
        return CompanyPackageTypes::Banner;
    }
}
