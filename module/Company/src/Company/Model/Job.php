<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Job model.
 *
 * @ORM\Entity
 */
class Job
{

    /**
     * The job id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The job's display name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The job's slug name.
     *
     * @ORM\Column(type="string")
     */
    protected $slugName;
    /**
     * The job's status.
     *
     * @ORM\Column(type="boolean")
     */
    protected $active;

    /**
     * The job's website.
     *
     * @ORM\Column(type="string")
     */
    protected $website;

    /**
     * The job's phone.
     *
     * @ORM\Column(type="string")
     */
    protected $phone;

    /**
     * The job's email.
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * The job's description.
     *
     * @ORM\Column(type="text")
     */
    protected $description;
    
    /**
     * The job's language.
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * The job's packet.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\CompanyPacket", inversedBy="jobs")
     */
    protected $packet;

    /**
     * Constructor
     */
    public function __construct()
    {
        // noting to do
    }

    /**
     * Get the job's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the job's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set the job's name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * Get the job's slug name.
     * 
     * @return string the Jobs slug name
     */
    public function getSlugName()
    {
        return $this->slugName;
    }
    
    /**
     * Set the job's slug name.
     *
     * @param string $name
     */
    public function setSlugName($name)
    {
        $this->slugName = $name;
    }

    /**
     * Get the job's status.
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set the job's status.
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get the job's website.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set the job's website.
     *
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Get the job's phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the job's phone.
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get the job's email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the job's email.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get the job's description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the job's description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    /**
     * Get the job's language.
     * 
     * @return string language of the job
     */
    public function getLanguage() {
        return $this->language;   
    }
    
    /**
     * Set the job's language.
     * 
     * @param string $language language of the job
     */
    public function setLanguage($language) {
        $this->language = $language;
    }
    
    /**
     * Get the job's packet.
     *
     * @return CompanyPacket
     */
    public function getPacket()
    {
        return $this->packet;
    }

    /**
     * Set the job's packet.
     * 
     * @param CompanyPacket $packet the job's packet
     */
    public function setPacket(CompanyPacket $packet)
    {
        $this->packet = $packet;
    }
    
    // For zend2 forms
    public function getArrayCopy()
    {
        $array = get_object_vars($this);
        if ($this->getActive()){
            $array['active'] = 'active';
        }
        else{
            $array['active'] = 'inactive';
        }
        return $array;
    }
    public function exchangeArray($data){
        $this->name=(isset($data['name'])) ? $data['name'] : $this->getName();
        $this->slugName=(isset($data['slugName'])) ? $data['slugName'] : $this->getSlugName();
        $this->language=(isset($data['language'])) ? $data['language'] : $this->getLanguage();
//        $this->address=(isset($data['address'])) ? $data['address'] : $this->getAddress();
        $this->website=(isset($data['website'])) ? $data['website'] : $this->getWebsite();
        $lActive = $data['active'];
        if ($lActive === 'active'){
            $this->active=true;
        }
        else{
            $this->active=false;
        }
        $this->email=(isset($data['email'])) ? $data['email'] : $this->getEmail();
        $this->phone=(isset($data['phone'])) ? $data['phone'] : $this->getPhone();
        $this->description=(isset($data['description'])) ? $data['description'] : $this->getDescription();
    }
}
