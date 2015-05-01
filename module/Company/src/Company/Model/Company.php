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
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyI18n", mappedBy="company")
     */
    protected $translations;
    
    /**
     * The company's display name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The company's slug version of the name. (username)
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
     * The company's packets
     * 
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyPacket", mappedBy="company")
     */
    protected $packets;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $packets = new ArrayCollection();
        $translations = new ArrayCollection();
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
        return $this->translations;
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
     * Get the company's hidden status.
     *
     * @return boolean
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

    public function getNumberOfJobs(){
        $jobcount = 0;
        foreach($this->getPackets() as $packet){
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
    // For zend2 forms
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    
    public function exchangeArray($data) {
        $this->name=(isset($data['name'])) ? $data['name'] : $this->getName();
        $this->slugName=(isset($data['slugName'])) ? $data['slugName'] : $this->getSlugName();
        $this->languageNeutralId=(isset($data['languageNeutralId'])) ? $data['languageNeutralId'] : $this->languageNeutralId;
        $this->address=(isset($data['address'])) ? $data['address'] : $this->getAddress();
        $this->website=(isset($data['website'])) ? $data['website'] : $this->getWebsite();
        $this->email=(isset($data['email'])) ? $data['email'] : $this->getEmail();
        $this->phone=(isset($data['phone'])) ? $data['phone'] : $this->getPhone();
        $this->packets=(isset($data['packets'])) ? $data['packets'] : $this->getPackets();
    }
}
