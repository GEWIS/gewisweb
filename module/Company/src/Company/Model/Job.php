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
     * @ORM\Column(type="string")
     */
    protected $attachment;

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
    protected function getActive()
    {
        return $this->active;
    }

    public function isActive()
    {
        return $this->getActive() and $this->getPackage()->isActive();
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
     * Set the job's package.
     *
     * @param CompanyPackage $package the job's package
     */
    public function setPackage(CompanyPackage $package)
    {
        $this->package = $package;
    }

    // For zend2 forms
    /**
     * Returns an array containing all instance variables of this object
     *
     */
    public function getArrayCopy()
    {

        $array['name'] = $this->getName();
        $array['slugName'] = $this->getSlugName();
        $array['active'] = ($this->getActive()) ? '1' : '0';
        $array['attachment'] = $this->getAttachment();
        $array['language'] = $this->getLanguage();
        $array['website'] = $this->getWebsite();
        $array['email'] = $this->getEmail();
        $array['phone'] = $this->getPhone();
        $array['description'] = $this->getDescription();

        return $array;
    }

    /**
     * Returns the first argument if it is nonnul, otherwise, returns the second
     * argument
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
     * Sets all instance variables of this object to the values of the entries
     * in $data
     *
     * @param mixed $data
     */
    public function exchangeArray($data)
    {
        $this->setName($this->updateIfSet($data['name'],''));
        $this->setSlugName($this->updateIfSet($data['slugName'],''));
        $this->setLanguage($this->updateIfSet($data['language'],''));
        $this->setWebsite($this->updateIfSet($data['website'],''));
        $this->setEmail($this->updateIfSet($data['email'],''));
        $this->setPhone($this->updateIfSet($data['phone'],''));
        $this->setDescription($this->updateIfSet($data['description'],''));
        $this->setActive($data['active'] === '1');
    }
}
