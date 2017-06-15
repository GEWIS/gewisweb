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
     * The location(url) of an attachment describing the job.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $attachment;
    
    /**
     * The job's contact's name.
     *
     * @ORM\Column(type="string")
     */
    protected $contactName;
    
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
     * The job's timestamp.
     *
     * @ORM\Column(type="date")
     */
    protected $timestamp;

    /**
     * The job's language.
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * The job's package.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\CompanyJobPackage", inversedBy="jobs")
     */
    protected $package;

    /**
     * Constructor.
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
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    public function isActive()
    {
        return $this->getActive() and $this->getPackage()->isActive() && $this->getPackage()->getCompany()->isHidden();
    }

    /**
     * Set the job's status.
     *
     * @param bool $active
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
     * Get the job's attachment.
     *
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set the job's attachment.
     *
     * @param string $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }
    
    /**
     * Get the job's contact's name.
     *
     * @return string
     */
    public function getContactName()
    {
        return $this->contactName;
    }
    
    /**
     * Set the job's contact's name.
     *
     * @param string $name
     */
    public function setContactName($name)
    {
        $this->contactName = $name;
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
     * Get the job's timestamp.
     *
     * @return date
     */
    public function getTimestamp()
    {
        return $this->description;
    }

    /**
     * Set the job's timestamp.
     *
     * @param string $timestamp
     */
    public function setTimeStamp($timestamp)
    {
        $this->timestamp = $timestamp;
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
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the job's language.
     *
     * @param string $language language of the job
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get the job's package.
     *
     * @return CompanyPackage
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Get the job's company
     *
     * @return company
     */
    public function getCompany()
    {
        return $this->getPackage()->getCompany();
    }

    /**
     * Set the job's package.
     *
     * @param CompanyPackage $package the job's package
     */
    public function setPackage(CompanyPackage $package)
    {
        $this->package = $package;
    }
}
