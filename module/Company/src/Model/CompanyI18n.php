<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    ManyToOne,
};

/**
 * CompanyI18n model.
 * Contains language-specific information of companies.
 */
#[Entity]

class CompanyI18n //implements ArrayHydrator (for zend2 form)
{
    /**
     * Id of the company details.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Company entity that these details are for.
     */
    #[ManyToOne(
        targetEntity: Company::class,
        inversedBy: "translations",
        cascade: ["persist"],
    )]
    protected Company $company;

    /**
     * The company's slogan.
     */
    #[Column(type: "string")]
    protected string $slogan;

    /**
     * The company's logo.
     */
    #[Column(type: "string")]
    protected string $logo;

    /**
     * The company's (HTML) description.
     */
    #[Column(type: "text")]
    protected string $description;

    /**
     * The company's website.
     */
    #[Column(type: "string")]
    protected string $website;

    /**
     * The language that this company record is written in.
     */
    #[Column(type: "string")]
    protected string $language;

    /**
     * Constructor.
     *
     * @param $locale
     * @param $company
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
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the company entity that these details are for.
     *
     * @return Company company that these details are for
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * Set the company entity that these details are for.
     *
     * @param Company $company company that these details are for
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Get the company's slogan.
     *
     * @return string
     */
    public function getSlogan(): string
    {
        return $this->slogan;
    }

    /**
     * Set the company's slogan.
     *
     * @param string $slogan
     */
    public function setSlogan(string $slogan): void
    {
        $this->slogan = $slogan;
    }

    /**
     * Get the company's logo.
     *
     * @return string
     */
    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * Set the company's logo.
     *
     * @param string $logo
     */
    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * Get the company's description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the company's description.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the company's website.
     *
     * @return string
     */
    public function getWebsite(): string
    {
        return $this->website;
    }

    /**
     * Set the company's website.
     *
     * @param string $website
     */
    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    /**
     * Get the company's language.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the company's language.
     *
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
}
