<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};

/**
 * CompanyBannerPackage model.
 */
#[Entity]
class CompanyBannerPackage extends CompanyPackage //implements RoleInterface, ResourceInterface
{
    /**
     * The banner's image's URL.
     */
    #[Column(type: "string")]
    protected string $image;

    /**
     * Get the banner's image's URL.
     *
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * Set the banner's image's URL.
     *
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'banner';
    }
}
