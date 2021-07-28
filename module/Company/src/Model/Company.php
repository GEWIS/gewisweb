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
};
use Exception;

/**
 * Company model.
 */
#[Entity]
class Company // implements ArrayHydrator (for zend2 form)
{
    /**
     * The company id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Translations of details of the company.
     * Are of type \Company\Model\CompanyI18n.
     */
    #[OneToMany(
        targetEntity: "Company\Model\CompanyI18n",
        mappedBy: "company",
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected Collection $translations;

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
    #[Column(type: "string")]
    protected string $contactName;

    /**
     * The company's address.
     */
    #[Column(type: "string")]
    protected string $address;

    /**
     * The company's email.
     */
    #[Column(type: "string")]
    protected string $email;

    /**
     * The company's phone.
     */
    #[Column(type: "string")]
    protected string $phone;

    /**
     * Whether the company is hidden.
     */
    #[Column(type: "boolean")]
    protected bool $hidden;

    /**
     * The company's packages.
     */
    #[OneToMany(
        targetEntity: "Company\Model\CompanyPackage",
        mappedBy: "company",
        cascade: ["persist", "remove"],
    )]
    protected Collection $packages;

    private int $languageNeutralId;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packages = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    /**
     * Get the company's id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the company's translations.
     *
     * @return Collection|array
     */
    public function getTranslations(): Collection|array
    {
        return $this->translations;
    }

    /**
     * Add a translation.
     *
     * @param CompanyI18n $translation
     */
    public function addTranslation(CompanyI18n $translation): void
    {
        $this->translations->add($translation);
    }

    /**
     * Remove a translation.
     *
     * @param CompanyI18n $translation Translation to remove
     */
    public function removeTranslation(CompanyI18n $translation): void
    {
        $this->translations->removeElement($translation);
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
     * @return string
     */
    public function getContactName(): string
    {
        return $this->contactName;
    }

    /**
     * Set the company's contact's name.
     *
     * @param string $name
     */
    public function setContactName(string $name): void
    {
        $this->contactName = $name;
    }

    /**
     * Get the company's address.
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Set the company's address.
     *
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * Get the company's email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the company's email.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the company's phone.
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Set the company's phone.
     *
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
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
        // TODO check whether package is not expired
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
     * Returns the available languages (translations) for this company.
     *
     * @return Collection
     */
    public function getAvailableLanguages(): Collection
    {
        return $this->getTranslations()->map(
            function ($value) {
                return $value->getLanguage();
            }
        );
    }

    /**
     * If this object contains a translation for a given locale, it is returned, otherwise null is returned.
     *
     * @param string $locale
     *
     * // TODO: Determine correct return type.
     * @return mixed|null
     */
    public function getTranslationFromLocale(string $locale): mixed
    {
        $companyLanguages = $this->getAvailableLanguages();

        if ($companyLanguages->contains($locale)) {
            return $this->getTranslations()[$companyLanguages->indexOf($locale)];
        }

        return null;
    }

    /**
     * Updates the variable if the first argument is set, Otherwise, it will
     * use the second argument.
     *
     * @param mixed $object
     * @param mixed $default
     *
     * @return mixed
     */
    private static function updateIfSet(mixed $object, mixed $default): mixed
    {
        if (isset($object)) {
            return $object;
        }

        return $default;
    }

    /**
     * Returns the translation identified by $language.
     *
     * Note, does not set $logo, the user should set this property himself
     *
     * @param array $data
     * @param string $language
     *
     * @return CompanyI18n|null
     *
     * @throws Exception
     */
    public function updateTranslationFromArray(array $data, string $language): ?CompanyI18n
    {
        if ('' !== $language) {
            $translation = $this->getTranslationFromLocale($language);

            if (is_null($translation)) {
                $translation = new CompanyI18n($language, $this);
            }

            $language = $language . '_';

            // Translated properties
            $translation->setWebsite($this->updateIfSet($data[($language) . 'website'], ''));
            $translation->setSlogan($this->updateIfSet($data[$language . 'slogan'], ''));
            $translation->setDescription($this->updateIfSet($data[$language . 'description'], ''));

            // Do not set logo, because most likely, $data[logo] is bogus.
            // Instead, the user should set this property himself later.
            return $translation;
        }

        return null;
    }

    /**
     * Updates this object with values in the form of getArrayCopy().
     *
     * @throws Exception
     */
    public function exchangeArray($data): void
    {
        $languages = $data['languages'];

        foreach ($languages as $language) {
            $this->updateTranslationFromArray($data, $language);
        }

        $this->setName($this->updateIfSet($data['name'], ''));
        $this->setContactName($this->updateIfSet($data['contactName'], ''));
        $this->setSlugName($this->updateIfSet($data['slugName'], ''));
        $this->setAddress($this->updateIfSet($data['address'], ''));
        $this->setEmail($this->updateIfSet($data['email'], ''));
        $this->setPhone($this->updateIfSet($data['phone'], ''));
        $this->setHidden($this->updateIfSet($data['hidden'], ''));
    }

    /**
     * Returns an array copy with varName=> var for all variables except the
     * translation.
     *
     * It will aso add keys in the form $lan_varName=>$this->getTranslationFromLocale($lang)=>var
     *
     * @return array
     */
    public function getArrayCopy(): array
    {
        $arraycopy = [];
        $arraycopy['id'] = $this->getId();
        $arraycopy['name'] = $this->getName();
        $arraycopy['slugName'] = $this->getSlugName();
        $arraycopy['contactName'] = $this->getContactName();
        $arraycopy['email'] = $this->getEmail();
        $arraycopy['address'] = $this->getAddress();
        $arraycopy['phone'] = $this->getPhone();
        $arraycopy['hidden'] = $this->getHidden();

        // Languages
        $arraycopy['languages'] = [];
        foreach ($this->getTranslations() as $translation) {
            $arraycopy[$translation->getLanguage() . '_' . 'slogan'] = $translation->getSlogan();
            $arraycopy[$translation->getLanguage() . '_' . 'website'] = $translation->getWebsite();
            $arraycopy[$translation->getLanguage() . '_' . 'description'] = $translation->getDescription();
            $arraycopy[$translation->getLanguage() . '_' . 'logo'] = $translation->getLogo();
            $arraycopy['languages'][] = $translation->getLanguage();
        }

        return $arraycopy;
    }
}
