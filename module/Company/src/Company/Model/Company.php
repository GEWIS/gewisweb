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
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyI18n", mappedBy="company", cascade={"persist"})
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
     * The company's packets.
     * 
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyPacket", mappedBy="company")
     */
    protected $packets;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packets = new ArrayCollection();
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

        return array();
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

        // When any packet is not expired, the company should be shown to the user
        foreach($this->getPackets() as $packet) {
            if(!$packet->isExpired()){
                $visible = true;
            }
        }

        // Except when it is explicitly marked as hidden.
        return $visible && !$this->getHidden();
    }
    /**
     * Get the company's hidden status.
     *
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
        // TODO check whether packet is not expired
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
     * Get the company's packets.
     *
     * @return CompanyPacket
     */
    public function getPackets()
    {
        return $this->packets;
    }

    public function getNumberOfJobs()
    {
        $jobcount = 0;
        if (is_null($this->getPackets())) {
            return $jobcount;
        }
        foreach ($this->getPackets() as $packet) {
            $jobcount +=  $packet->getJobs()->count();
        }

        return $jobcount;
    }

    /**
     * Add a packet to the company.
     *
     * @param CompanyPacket $packet
     */
    public function addPacket(CompanyPacket $packet)
    {
        $this->packets->add($packet);
    }

    /**
     * Remove a packet from the company.
     * 
     * @param CompanyPacket $packet packet to remove
     */
    public function removePacket(CompanyPacket $packet)
    {
        $this->packets->removeElement($packet);
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
     * Returns an array copy with varName=>var for all variables except the translation. It will also add keys in the form $lang_varName=>$this->getTranslationFromLocale($lang)=>var
     *
     */
    public function getArrayCopy()
    {
        $arraycopy = get_object_vars($this);
        $arraycopy['languages'] = array();
        foreach ($this->getTranslations() as $translation) {
            $arraycopy[$translation->getLanguage().'_'.'slogan'] = $translation->getSlogan();
            $arraycopy[$translation->getLanguage().'_'.'website'] = $translation->getWebsite();
            $arraycopy[$translation->getLanguage().'_'.'description'] = $translation->getDescription();
            $arraycopy[$translation->getLanguage().'_'.'logo'] = $translation->getLogo();
            $arraycopy['languages'][] = $translation->getLanguage();
        }

        return $arraycopy;
    }

    
    /**
     * If this object contains an translation for a given locale, it is returned, else null is returned
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

    private function updateIfSet($object, $default)
    {
        if (isset($object)) {
            return $object;
        }
        return $default;
    }
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
            $translation->setLogo($this->updateIfSet($data[$language.'logo'], ''));
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
        $this->setName($this->updateIfSet($this->$language.'name',     ''));
        $this->setSlugName($this->updateIfSet($this->$language.'slugName', ''));
        $this->setAddress($this->updateIfSet($this->$language.'address',  ''));
        $this->setEmail($this->updateIfSet($this->$language.'email',    ''));
        $this->setPhone($this->updateIfSet($this->$language.'phone',    ''));
        $this->translations = $newTranslations;
    }
}
