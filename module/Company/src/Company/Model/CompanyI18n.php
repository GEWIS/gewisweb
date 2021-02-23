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
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="translations", cascade={"persist"})
     */
    protected $company;

    /**
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

    // TODO: Check if description is not reserved by PHP
    /**
     * The company's (HTML) description.
     *
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * The company's website.
     *
     * @ORM\Column(type="string")
     */
    protected $website;

    /**
     * The language that this company record is written in.
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * Constructor.
     */
    public function __construct($locale, $company)
    {
        $this->description = '';
        $this->website = '';
        $this->setLanguage($locale);
        $this->setCompany($company);
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
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the company entity that these details are for.
     *
     * @param Company $company company that these details are for
     */
    public function setCompany(Company $company)
    {
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

    public function remove()
    {
        $this->company = null;
    }

}
