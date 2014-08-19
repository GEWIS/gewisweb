<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;
//use Zend\Permissions\Acl\Role\RoleInterface;
//use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * CompanyBannerPacket model.
 *
 * @ORM\Entity
 */
class CompanyBannerPacket extends CompanyPacket //implements RoleInterface, ResourceInterface
{

    /**
     * The banner's image's URL.
     *
     * @ORM\Column(type="string")
     */
    protected $image;
        
    /**
     * The banner's HTML.
     *
     * @ORM\Column(type="string")
     */
    protected $html;
    
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
    
    /**
     * Get the banner's HTML.
     *
     * @return string
     */
    public function getHTML()
    {
        return $this->html;
    }
    
    /**
     * Set the banner's HTML.
     *
     * @param string $html
     */
    public function setHTML($html)
    {
        $this->html = $html;
    }
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // todo
    }

    public function publish()
    {
        // todo
    }
    
    public function unpublish()
    {
        // todo
    }
    
    public function create()
    {
        // todo
    }
    
    public function save()
    {
        // todo   
    }
    
    public function delete()
    {
        // todo
    }
    
    
}
