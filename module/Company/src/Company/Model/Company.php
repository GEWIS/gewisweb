<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;
//use Zend\Permissions\Acl\Role\RoleInterface;
//use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Company model.
 *
 * @ORM\Entity
 */
class Company //implements RoleInterface, ResourceInterface
{

    /**
     * The company id.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The company's name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;
    
    /**
     * The company's address.
     *
     * @ORM\Column(type="string")
     */
    protected $address;

    /**
     * The company's website.
     *
     * @ORM\Column(type="string")
     */
    protected $website;

    /**
     * The company's slogan.
     *
     * @ORM\Column(type="string")
     */
    protected $slogan;

    /**
     * The company's email.
     *
     * @ORM\Column(type="string")
     */
    protected $email;
    
    /**
     * The company's phone.
     *
     * @ORM\Column(type="string")
     */
    protected $phone;

    /**
     * The company's logo.
     *
     * @ORM\Column(type="string")
     */
    protected $logo;
    
    /**
     * The company's (HTML) description.
     *
     * @ORM\Column(type="string")
     */
    protected $description;
    
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // todo
    }

    /**
     * Get the company's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the company's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set the company's name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the company's address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
    
    /**
     * Set the company's address.
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }
    
    /**
     * Get the company's website.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }
    
    /**
     * Set the company's website.
     *
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }
    
    /**
     * Get the company's slogan.
     *
     * @return string
     */
    public function getSlogan()
    {
        return $this->slogan;
    }
    
    /**
     * Set the company's slogan.
     *
     * @param string $slogan
     */
    public function setSlogan($slogan)
    {
        $this->slogan = $slogan;
    }
    
    /**
     * Get the company's email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * Set the company's email.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
    
    /**
     * Get the company's phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
    
    /**
     * Set the company's phone.
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }
    
    /**
     * Get the company's logo.
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }
    
    /**
     * Set the company's logo.
     *
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }
    
    /**
     * Get the company's description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * Set the company's description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    
}
