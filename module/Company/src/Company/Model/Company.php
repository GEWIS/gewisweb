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
class Company //implements RoleInterface, ResourceInterface, ArrayHydrator (for zend2 form)
{

    /**
     * The company id.
     *
     * @ORM\Column(type="integer")
     */
    protected $languageNeutralId;
    
    /**
     * Version (language-unique) id of company representation.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;
        
    /**
     * The company's display name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The company's ascii version of the name. (username)
     *
     * @ORM\Column(type="string")
     */
    protected $asciiName;

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
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * The language that this company record is written in
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * The company's jobs.
     *
     * @ORM\Column(type="boolean")
     */
    protected $hidden;

    /**
     * The company's jobs.
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="company")
     */
    protected $jobs;
    
    /**
     * The company's packet.
     *
     * @ORM\ManyToOne(targetEntity="CompanyPacket", inversedBy="companies")
     */
    protected $packet;
    
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

    public function getAsciiName()
    {
        return $this->asciiName;
    }

    public function setAsciiName($asciiName)
    {
        $this->asciiName = $asciiName;
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

    /**
     * Get the company's jobs.
     *
     * @return Job[]
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Set the company's jobs.
     *
     * @param Job[] $jobs
     */
    public function setJobs($jobs)
    {
        $this->jobs = $jobs;
    }
    
    /**
     * Get the company's packet.
     *
     * @return CompanyPacket
     */
    public function getPacket()
    {
        return $this->packet;
    }

    /**
     * Set the company's packet.
     *
     * @param CompanyPacket $packet
     */
    public function setPacket($packet)
    {
        $this->packet = $packet;
    }
    
    /**
     * Get the company's hidden status.
     *
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the company's language.
     *
     * @param string $language
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }
    /**
     * Get the company's language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the company's language.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }
    
    // For zend2 forms
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    
    public function exchangeArray($data) {
        $this->name=(isset($data['name'])) ? $data['name'] : $this->getName();
        $this->asciiName=(isset($data['asciiName'])) ? $data['asciiName'] : $this->getAsciiName();
        $this->address=(isset($data['address'])) ? $data['address'] : $this->getAddress();
        $this->website=(isset($data['website'])) ? $data['website'] : $this->getWebsite();
        $this->slogan=(isset($data['slogan'])) ? $data['slogan'] : $this->getSlogan();
        $this->email=(isset($data['email'])) ? $data['email'] : $this->getEmail();
        $this->logo=(isset($data['logo'])) ? $data['logo'] : $this->getLogo();
        $this->phone=(isset($data['phone'])) ? $data['phone'] : $this->getPhone();
        $this->description=(isset($data['description'])) ? $data['description'] : $this->getDescription();
        $this->jobs=(isset($data['jobs'])) ? $data['jobs'] : $this->getJobs();
        $this->packet=(isset($data['packet'])) ? $data['packet'] : $this->getPacket();
    }
}
