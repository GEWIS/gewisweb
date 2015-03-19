<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyI18n model.
 * Contains language-specific information of companies.
 *
 * @ORM\Entity
 */
class CompanyI18n //implements ArrayHydrator (for zend2 form)
{

    /**
     * Id of the company details.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;
    
    /**
     * Company entity that these details are for.
     * 
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="translations")
     */
    protected $company;
        
    /*
     * The company's slogan.
     *
     * @ORM\Column(type="string")
     */
    protected $slogan;

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
     * Constructor
     */
    public function __construct()
    {
        // nothing to do
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
     * Get the company entity that these details are for.
     * 
     * @return Company company that these details are for
     */
    public function getCompany() {
        return $this->company;
    }
    
    /**
     * Set the company entity that these details are for.
     * 
     * @param Company $company company that these details are for
     */
    public function setCompany(Company $company) {
        $this->company = $company;
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
