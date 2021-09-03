<?php

namespace Company\Model;

use DateTime;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    OneToMany,
    OneToOne,
};
use Exception;

/**
 * Company model.
 */
#[Entity]
class Company
{
    /**
     * The company id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The company's display name.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * The company's slug version of the name. (username).
     */
    #[Column(type: "string")]
    protected string $slugName;

    /**
     * The company's contact's name.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactName;

    /**
     * The company's address.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactAddress;

    /**
     * The company's email.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactEmail;

    /**
     * The company's phone.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactPhone;

    /**
     * Company slogan.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $slogan;

    /**
     * Company logo.
     */
    #[Column(type: "string")]
    protected ?string $logo = null;

    /**
     * Company description.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $description;

    /**
     * Company website.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $website;

    /**
     * Whether the company is hidden.
     */
    #[Column(type: "boolean")]
    protected bool $hidden;

    /**
     * The company's packages.
     */
    #[OneToMany(
        targetEntity: CompanyPackage::class,
        mappedBy: "company",
        cascade: ["persist", "remove"],
    )]
    protected Collection $packages;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packages = new ArrayCollection();
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
     * Get the company's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the company's name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the company's slug name.
     *
     * @return string the company's slug name
     */
    public function getSlugName(): string
    {
        return $this->slugName;
    }

    /**
     * Sets the company's slug name.
     *
     * @param string $slugName the new slug name
     */
    public function setSlugName(string $slugName): void
    {
        $this->slugName = $slugName;
    }

    /**
     * Get the company's contact's name.
     *
     * @return string|null
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * Set the company's contact's name.
     *
     * @param string|null $name
     */
    public function setContactName(?string $name): void
    {
        $this->contactName = $name;
    }

    /**
     * Get the company's address.
     *
     * @return string|null
     */
    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    /**
     * Set the company's address.
     *
     * @param string|null $contactAddress
     */
    public function setContactAddress(?string $contactAddress): void
    {
        $this->contactAddress = $contactAddress;
    }

    /**
     * Get the company's email.
     *
     * @return string|null
     */
    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    /**
     * Set the company's email.
     *
     * @param string|null $contactEmail
     */
    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get the company's phone.
     *
     * @return string|null
     */
    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    /**
     * Set the company's phone.
     *
     * @param string|null $contactPhone
     */
    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    /**
     * Get the company's slogan.
     *
     * @return CompanyLocalisedText
     */
    public function getSlogan(): CompanyLocalisedText
    {
        return $this->slogan;
    }

    /**
     * Set the company's slogan.
     *
     * @param CompanyLocalisedText $slogan
     */
    public function setSlogan(CompanyLocalisedText $slogan): void
    {
        $this->slogan = $slogan;
    }

    /**
     * Get the company's logo.
     *
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * Set the company's logo.
     *
     * @param string|null $logo
     */
    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * Get the company's description.
     *
     * @return CompanyLocalisedText
     */
    public function getDescription(): CompanyLocalisedText
    {
        return $this->description;
    }

    /**
     * Set the company's description.
     *
     * @param CompanyLocalisedText $description
     */
    public function setDescription(CompanyLocalisedText $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the company's website.
     *
     * @return CompanyLocalisedText
     */
    public function getWebsite(): CompanyLocalisedText
    {
        return $this->website;
    }

    /**
     * Set the company's description.
     *
     * @param CompanyLocalisedText $website
     */
    public function setWebsite(CompanyLocalisedText $website): void
    {
        $this->website = $website;
    }

    /**
     * Return true if the company should not be visible to the user, and false if it should be visible to the user.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        $visible = false;

        // When any packages is not expired, the company should be shown to the user
        foreach ($this->getPackages() as $package) {
            if (!$package->isExpired(new DateTime())) {
                $visible = true;
            }
        }

        // Except when it is explicitly marked as hidden.
        return !$visible || $this->getHidden();
    }

    /**
     * Get the company's hidden status.
     *
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set the company's hidden status.
     *
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the company's packages.
     *
     * @return Collection of CompanyPackages
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    /**
     * Get the number of packages.
     *
     * @return integer the number of packages
     */
    public function getNumberOfPackages(): int
    {
        return count($this->packages);
    }

    /**
     * Returns the number of jobs that are contained in all packages of this
     * company.
     *
     * @return int
     */
    public function getNumberOfJobs(): int
    {
        $jobCount = function ($package) {
            if ('job' == $package->getType()) {
                return $package->getJobs()->count();
            }

            return 0;
        };

        return array_sum(array_map($jobCount, $this->getPackages()->toArray()));
    }

    /**
     * Returns the number of jobs that are contained in all active packages of this
     * company.
     *
     * // TODO: Determine correct type of parameter.
     * @param $category
     *
     * @return int
     */
    public function getNumberOfActiveJobs($category = null): int
    {
        $jobCount = function ($package) use ($category) {
            return $package->getNumberOfActiveJobs($category);
        };

        return array_sum(array_map($jobCount, $this->getPackages()->toArray()));
    }

    /**
     * Returns the number of expired packages.
     *
     * @return int
     */
    public function getNumberOfExpiredPackages(): int
    {
        return count(
            array_filter(
                $this->getPackages()->toArray(),
                function ($package) {
                    return $package->isExpired(new DateTime());
                }
            )
        );
    }

    /**
     * Returns true if a banner is active, and false when there is no banner active.
     *
     * @return array
     */
    public function getFeaturedLanguages(): array
    {
        return array_map(
            function ($package) {
                return $package->getLanguage();
            },
            array_filter(
                $this->getPackages()->toArray(),
                function ($package) {
                    return 'featured' === $package->getType() && $package->isActive();
                }
            )
        );
    }

    /**
     * Returns true if a banner is active, and false when there is no banner active.
     *
     * @return bool
     */
    public function isBannerActive(): bool
    {
        $banners = array_filter(
            $this->getPackages()->toArray(),
            function ($package) {
                return 'banner' === $package->getType() && $package->isActive();
            }
        );

        return !empty($banners);
    }

    /**
     * Updates this object with values in the form of getArrayCopy(). This does not include the logo.
     *
     * @param array $data
     *
     * @throws Exception
     */
    public function exchangeArray(array $data): void
    {
        $this->setName($data['name']);
        $this->setSlugName($data['slugName']);
        $this->setContactName($data['contactName']);
        $this->setContactAddress($data['contactAddress']);
        $this->setContactEmail($data['contactEmail']);
        $this->setContactPhone($data['contactPhone']);
        $this->setHidden($data['hidden']);

        $this->getSlogan()->updateValues($data['sloganEn'], $data['slogan']);
        $this->getWebsite()->updateValues($data['websiteEn'], $data['website']);
        $this->getDescription()->updateValues($data['descriptionEn'], $data['description']);
    }

    /**
     * Returns an array copy with all attributes.
     *
     * @return array
     */
    public function toArray(): array
    {
        $arraycopy = [];

        $arraycopy['name'] = $this->getName();
        $arraycopy['slugName'] = $this->getSlugName();
        $arraycopy['logo'] = $this->getLogo();
        $arraycopy['contactName'] = $this->getContactName();
        $arraycopy['contactEmail'] = $this->getContactEmail();
        $arraycopy['contactAddress'] = $this->getContactAddress();
        $arraycopy['contactPhone'] = $this->getContactPhone();
        $arraycopy['hidden'] = $this->getHidden();

        // Languages
        $arraycopy['slogan'] = $this->getSlogan()->getValueNL();
        $arraycopy['sloganEn'] = $this->getSlogan()->getValueEN();
        $arraycopy['website'] = $this->getWebsite()->getValueNL();
        $arraycopy['websiteEn'] = $this->getWebsite()->getValueEN();
        $arraycopy['description'] = $this->getDescription()->getValueNL();
        $arraycopy['descriptionEn'] = $this->getDescription()->getValueEN();

        return $arraycopy;
    }
}
