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
        return $this->hidden;
    }

    /**
     * Get the approval's company.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
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
