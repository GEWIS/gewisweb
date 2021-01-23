<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

/**
 * Company model.
 *
 * @ORM\Entity
 */
class Company // implements ArrayHydrator (for zend2 form)
{
    /**
     * The company id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Translations of details of the company.
     * Are of type \Company\Model\CompanyI18n.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyI18n", mappedBy="company", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $translations;

    /**
     * The company's display name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The company's slug version of the name. (username).
     *
     * @ORM\Column(type="string")
     */
    protected $slugName;
    
    /**
     * The company's contact's name.
     *
     * @ORM\Column(type="string")
     */
    protected $contactName;

    /**
     * The company's address.
     *
     * @ORM\Column(type="string")
     */
    protected $address;

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
     * Whether the company is hidden.
     *
     * @ORM\Column(type="boolean")
     */
    protected $hidden;

    /**
     * The company's packages.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyPackage", mappedBy="company", cascade={"persist", "remove"})
     */
    protected $packages;

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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the company's translations.
     *
     * @return array
     */
    public function getTranslations()
    {
        if (!is_null($this->translations)) {
            return $this->translations;
        }

        return [];
    }

    /**
     * Add a translation.
     *
     * @param CompanyI18n $translation
     */
    public function addTranslation(CompanyI18n $translation)
    {
        $this->translations->add($translation);
    }

    /**
     * Remove a translation.
     *
     * @param CompanyI18n $translation Translation to remove
     */
    public function removeTranslation(CompanyI18n $translation)
    {
        $this->translations->removeElement($translation);
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
     * Gets the company's slug name.
     *
     * @return string the company's slug name
     */
    public function getSlugName()
    {
        return $this->slugName;
    }

    /**
     * Sets the company's slug name.
     *
     * @param string $slugName the new slug name
     */
    public function setSlugName($slugName)
    {
        $this->slugName = $slugName;
    }
    
    /**
     * Get the company's contact's name.
     *
     * @return string
     */
    public function getContactName()
    {
        return $this->contactName;
    }
    
    /**
     * Set the company's contact's name.
     *
     * @param string $name
     */
    public function setContactName($name)
    {
        $this->contactName = $name;
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
     *
     * Return true if the company should not be visible to the user, and false if it should be visible to the user
     *
     */

    public function isHidden()
    {
        $visible = false;

        // When any packages is not expired, the company should be shown to the user
        foreach($this->getPackages() as $package) {
            if(!$package->isExpired(new \DateTime)){
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
    public function getHidden()
    {
        return $this->hidden;
        // TODO check whether package is not expired
    }

    /**
     * Set the company's hidden status.
     *
     * @param string $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the company's packages.
     *
     * @return CompanyPackages
     */
    public function getPackages()
    {
        return $this->packages;
    }


    /**
     * Get the number of packages.
     *
     * @return the number of packages
     */
    public function getNumberOfPackages()
    {
        return count($this->packages);
    }
    /**
     * Returns the number of jobs that are contained in all packages of this
     * company.
     *
     */
    public function getNumberOfJobs()
    {
        $jobCount = function ($package) {
            if ($package->getType() == 'job') {
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
     */
    public function getNumberOfActiveJobs($category = null)
    {
        $jobCount = function ($package) use ($category) {
            return $package->getNumberOfActiveJobs($category);
        };

        return array_sum(array_map($jobCount, $this->getPackages()->toArray()));
    }

    /**
     * Returns the number of expired packages
     *
     */
    public function getNumberOfExpiredPackages()
    {
        return count(array_filter($this->getPackages()->toArray(), function ($package) {
            return $package->isExpired(new \DateTime);
        }));
    }

    /**
     * Returns true if a banner is active, and false when there is no banner active
     *
     */
    public function getFeaturedLanguages()
    {
        return array_map(
            function ($package) {
                return $package->getLanguage();
            },
            array_filter($this->getPackages()->toArray(), function ($package) {
                return $package->getType() === 'featured' && $package->isActive();

            })
        );
    }

    /**
     * Returns true if a banner is active, and false when there is no banner active
     *
     */
    public function isBannerActive()
    {
        $banners = array_filter($this->getPackages()->toArray(), function ($package) {
            return $package->getType() === 'banner' && $package->isActive();

        });

        return !empty($banners);
    }


    /**
     * Get the company's language.
     *
     * @return Integer
     */
    public function getLanguageNeutralId()
    {
        return $this->languageNeutralId;
    }
    /**
     * Set the company's language neutral id.
     *
     * @param Integer $languageNeutralId
     */
    public function setLanguageNeutralId($language)
    {
        $this->languageNeutralId = $language;
    }


    /**
     * If this object contains an translation for a given locale, it is returned, otherwise null is returned
     *
     */
    public function getTranslationFromLocale($locale)
    {
        $translation = null;

        $companyLanguages = $this->getTranslations()->map(function ($value) {
                    return $value->getLanguage();
                });

        if ($companyLanguages->contains($locale)) {
            $translation = $this->getTranslations()[$companyLanguages->indexOf($locale)];
        }

        return $translation;
    }

    /**
     * Updates the variable if the first argument is set, Otherwise, it will
     * use the second argument.
     *
     * @param mixed $object
     * @param mixed $default
     */
    private function updateIfSet($object, $default)
    {
        if (isset($object)) {
            return $object;
        }
        return $default;
    }
    /**
     * Returns the translation identified by $language
     *
     * Note, does not set $logo, the user should set this property himself
     *
     * @param mixed $data
     * @param mixed $language
     */
    public function getTranslationFromArray($data, $language)
    {

        if ($language !== '') {
            $translation = $this->getTranslationFromLocale($language);
            if (is_null($translation)){

                $translation = new CompanyI18n($language, $this);
            }
            $language = $language.'_';

            // Translated properties
            $translation->setWebsite($this->updateIfSet($data[($language).'website'], ''));
            $translation->setSlogan($this->updateIfSet($data[$language.'slogan'], ''));
            $translation->setDescription($this->updateIfSet($data[$language.'description'], ''));

            // Do not set logo, because most likely, $data[logo] is bogus.
            // Instead, the user should set this property himself later.
            return $translation;
        }
    }

    /**
     * Updates this object with values in the form of getArrayCopy()
     *
     */
    public function exchangeArray($data)
    {
        $languages = $data['languages'];

        $newTranslations = new ArrayCollection();

        foreach ($languages as $language) {
            $newTranslationObject = $this->getTranslationFromArray($data,$language);
            $newTranslations->add($newTranslationObject);
        }

        // Delete old translations
        foreach ($this->getTranslations() as $translation) {
            if (!$newTranslations->contains($translation)) {
                $translation->remove();
            }
        }
        $this->setName($this->updateIfSet($data['name'],''));
        $this->setContactName($this->updateIfSet($data['contactName'], ''));
        $this->setSlugName($this->updateIfSet($data['slugName'], ''));
        $this->setAddress($this->updateIfSet($data['address'],  ''));
        $this->setEmail($this->updateIfSet($data['email'],''));
        $this->setPhone($this->updateIfSet($data['phone'],''));
        $this->setHidden($this->updateIfSet($data['hidden'],''));
        $this->translations = $newTranslations;
    }

    /**
     * Returns an array copy with varName=> var for all variables except the
     * translation.
     *
     * It will aso add keys in the form $lan_varName=>$this->getTranslationFromLocale($lang)=>var
     *
     */
    public function getArrayCopy()
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
            $arraycopy[$translation->getLanguage().'_'.'slogan'] = $translation->getSlogan();
            $arraycopy[$translation->getLanguage().'_'.'website'] = $translation->getWebsite();
            $arraycopy[$translation->getLanguage().'_'.'description'] = $translation->getDescription();
            $arraycopy[$translation->getLanguage().'_'.'logo'] = $translation->getLogo();
            $arraycopy['languages'][] = $translation->getLanguage();
        }

        return $arraycopy;
    }
}
