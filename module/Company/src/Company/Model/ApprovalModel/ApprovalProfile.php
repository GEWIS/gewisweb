<?php


namespace Company\Model\ApprovalModel;
use Company\Model\ApprovalModel\ApprovalAbstract;
use Company\Model\ApprovalModel\ApprovalCompanyI18n;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Company\Model\Company;

/**
 * VacancyApproval model.
 *
 *
 * @ORM\Entity
 *
 *
 */
class ApprovalProfile implements ApprovalAbstract{



    /**
     * The profile approvals company
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="translations", cascade={"persist"})
     */
    protected $company;

    /**
     * The profile approvals approved status
     *
     * @ORM\Column(type="boolean")
     */
    protected $rejected = false;


    // TODO add other profile variables
    /**
     * The company id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @ORM\OneToOne(targetEntity="User\Model\Company")
     */
    protected $id;

    /**
     * Translations of details of the company.
     * Are of type \Company\Model\CompanyI18n.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\ApprovalModel\ApprovalCompanyI18n", mappedBy="company", cascade={"persist", "remove"}, orphanRemoval=true)
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
     * The company's contact email
     *
     * @ORM\Column(type="string")
     */
    protected $contactEmail;

    /**
     * The company's public email.
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
     * The company's phone.
     *
     * @ORM\Column(type="integer")
     */
    protected $highlightCredits;

    /**
     * The company banner credits.
     *
     * @ORM\Column(type="integer")
     */
    protected $bannerCredits;

    /**
     * The job's category.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\JobSector")
     */
    protected $sector;


    /**
     * @return mixed
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @return mixed
     */
    public function getSlugName()
    {
        return $this->slugName;
    }

    /**
     * @return mixed
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return mixed
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @return ArrayCollection
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @return mixed
     */
    public function getHighlightCredits()
    {
        return $this->highlightCredits;
    }

    /**
     * @return mixed
     */
    public function getBannerCredits()
    {
        return $this->bannerCredits;
    }

    /**
     * @return mixed
     */
    public function getEmailSubscription()
    {
        return $this->emailSubscription;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @param mixed $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @param ArrayCollection $packages
     */
    public function setPackages(ArrayCollection $packages)
    {
        $this->packages = $packages;
    }

    /**
     * @param mixed $highlightCredits
     */
    public function setHighlightCredits($highlightCredits)
    {
        $this->highlightCredits = $highlightCredits;
    }

    /**
     * @param mixed $bannerCredits
     */
    public function setBannerCredits($bannerCredits)
    {
        $this->bannerCredits = $bannerCredits;
    }

    /**
     * @param mixed $emailSubscription
     */
    public function setEmailSubscription($emailSubscription)
    {
        $this->emailSubscription = $emailSubscription;
    }

    /**
     * @return mixed
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @param bool $rejected
     */
    public function setRejected(bool $rejected)
    {
        $this->rejected = $rejected;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $translations
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }

    /**
     * @param mixed $contactName
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param mixed $sector
     */
    public function setSector($sector)
    {
        $this->sector = $sector;
    }

    /**
     * The companies email subscription.
     *
     * @ORM\Column(type="boolean")
     */
    protected $emailSubscription;

    /**
     * Get the approval's id.
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
     * Get the company's contact email.
     *
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * Set the company's contact email.
     *
     * @param string $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get the company's contact email.
     *
     * @return string
     */
    public function getHighlightCredits()
    {
        return $this->highlightCredits;
    }

    /**
     * Set the company's contact email.
     *
     * @param string $highlightCredits
     */
    public function setHighlightCredits($highlightCredits)
    {
        $this->highlightCredits = $highlightCredits;
    }

    /**
     * Get the company's contact email.
     *
     * @return string
     */
    public function getBannerCredits()
    {
        return $this->bannerCredits;
    }

    /**
     * Set the company's contact email.
     *
     * @param string $bannerCredits
     */
    public function setBannerCredits($bannerCredits)
    {
        $this->bannerCredits = $bannerCredits;
    }

    /**
     * Get the company's public email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the company's public email.
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
     * @return mixed
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * @param mixed $sector
     */
    public function setSector($sector)
    {
        $this->sector = $sector;
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

    public function getJobPackageId() {
        $packages = $this->getPackages()->toArray();
        foreach ($packages as &$value) {
            if ($value->getType() == "job") {
                return $value->getId();
            }
        }

        return null;
    }

    /**
     * Returns the number of expired packages
     *
     */
    public function getNumberOfExpiredPackages()
    {
        return count(array_filter($this->getPackages()->toArray(), function ($package) {
            return $package->isExpired(new DateTime());
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
        $companyLanguages = $this->getTranslations()->map(function ($value) {
            return $value->getLanguage();
        });

        if ($companyLanguages->contains($locale)) {
            return $this->getTranslations()[$companyLanguages->indexOf($locale)];
        }

        throw new \Exception(
            sprintf(
                'Requested non-existent translation for locale %s of company with language neutral id %d',
                $locale,
                $this->getLanguageNeutralId()
            )
        );
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
    }

    /**
     *Splits a sentence $string into several $words based on whitespaces
     *Then checks that each $word has at most $max characters
     *If any of the words exceed $max characters the $string is made to fit on one sentence by removing the excess characters and appending "..." to indicate that it has been cut short
     */
    function fixWordSize($string, $line_len, $max_chars) {
        if (strlen($string) > $max_chars) {                          //If the string exceeds $max_characters
            if ($line_len > $max_chars) {
                $string = substr($string, 0, $line_len);         //truncate it after $line_len
            } else {
                $string = substr($string, 0, $max_chars);         //truncate it after $max_chars
            }

            $string = $string.
                "...";                               //Append "..." to indicate truncation
        }

        $word = explode(" ", $string);                      //split $string into array of $words
        $rebuilt_string = "";
        for ($i = 0; $i < count($word); $i++) {
            if (strlen($word[$i]) > $line_len) {                     //Finding an oversized word
                $word[$i] = substr($word[$i], 0, $line_len);
                return $rebuilt_string.$word[$i].
                    "...";
            }
            $rebuilt_string = $rebuilt_string.$word[$i].
                " ";
        }
        return $string;
    }

    /**
     * Get the approval's approval status.
     *
     * @return boolean
     */
    public function getRejected()
    {
        return $this->rejected;
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
//        $arraycopy['name'] = $this->getName();
        $arraycopy['slugName'] = $this->getSlugName();
        $arraycopy['contactName'] = $this->getContactName();
//        $arraycopy['contactEmail'] = $this->getContactEmail();
        $arraycopy['email'] = $this->getEmail();
        $arraycopy['address'] = $this->getAddress();
//        $arraycopy['phone'] = $this->getPhone();
//        $arraycopy['highlightCredits'] = $this->getHighlightCredits();
//        $arraycopy['bannerCredits'] = $this->getBannerCredits();
//        $arraycopy['hidden'] = $this->getHidden();
        $arraycopy['sector'] = $this->getSector();

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

    /**
     * Updates this object with values in the form of getArrayCopy()
     *
     */
    public function exchangeArray($data)
    {
        $languages = $data['languages'];

        $newTranslations = new ArrayCollection();

        foreach ($languages as $language) {
            $newTranslationObject = $this->getTranslationFromArray($data, $language);
            $newTranslations->add($newTranslationObject);
        }

        // Delete old translations
        foreach ($this->getTranslations() as $translation) {
            if (!$newTranslations->contains($translation)) {
                $translation->remove();
            }
        }
        $this->setName($this->updateIfSet($data['name'], ''));
        $this->setContactName($this->updateIfSet($data['contactName'], ''));
        $this->setSlugName($this->updateIfSet($data['slugName'], ''));
        $this->setAddress($this->updateIfSet($data['address'], ''));
        $this->setContactEmail($this->updateIfSet($data['contactEmail'],''));
        $this->setEmail($this->updateIfSet($data['email'], ''));
        $this->setHighlightCredits($this->updateIfSet($data['highlightCredits'], 0));
        $this->setBannerCredits($this->updateIfSet($data['bannerCredits'], 0));
        $this->setPhone($this->updateIfSet($data['phone'], ''));
        $this->setHidden($this->updateIfSet($data['hidden'], ''));
        $this->setEmailSubscription($this->updateIfSet($data['emailSubscription'], false));
        $this->translations = $newTranslations;
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
     * @param mixed $slugName
     */
    public function setSlugName($slugName)
    {
        $this->slugName = $slugName;
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

            if (is_null($translation)) {
                $translation = new ApprovalCompanyI18n($language, $this);
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
    }

    /**
     * If this object contains an translation for a given locale, it is returned, otherwise null is returned
     *
     */
    public function getTranslationFromLocale($locale)
    {
        $companyLanguages = $this->getTranslations()->map(function ($value) {
            return $value->getLanguage();
        });

        if ($companyLanguages->contains($locale)) {
            return $this->getTranslations()[$companyLanguages->indexOf($locale)];
        }

//        throw new \Exception(
//            sprintf(
//                'Requested non-existent translation for locale %s of company with id %d',
//                $locale,
//                $this->getId()
//            )
//        );
    }

    /**
     * Add a translation.
     *
     * @param CompanyI18n $translation
     */
    public function addTranslation(ApprovalCompanyI18n $translation)
    {
        $this->translations->add($translation);
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packages = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }
}
