<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyBannerPackage model.
 *
 * @ORM\Entity
 */
class CompanyBannerPackage extends CompanyPackage //implements RoleInterface, ResourceInterface
{
    /**
     * The banner's image's URL.
     *
     * @ORM\Column(type="string")
     */
    protected $image;

    /**
     * Get the banner's image's URL.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set the banner's image's URL.
     *
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getType()
    {
        return 'banner';
    }
}
