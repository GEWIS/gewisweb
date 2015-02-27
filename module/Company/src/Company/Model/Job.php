<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;
//use Zend\Permissions\Acl\Role\RoleInterface;
//use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Job model.
 *
 * @ORM\Entity
 */
class Job //implements RoleInterface, ResourceInterface
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
     * The job's ascii name.
     *
     * @ORM\Column(type="string")
     */
    protected $asciiName;
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
     * The job's company.
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="jobs")
     */
    protected $company;



    /**
     * Constructor
     */
    public function __construct()
    {
        // todo
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

    public function getAsciiName()
    {
        return $this->asciiName;
    }
    /**
     * Set the job's name.
     *
     * @param string $name
     */
    public function setAsciiName($name)
    {
        $this->asciiName = $name;
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
     * Get the job's company.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company)
    {
        $this->company = $company;
    }
    // For zend2 forms
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    public function exchangeArray($data){
        $this->name=(isset($data['name'])) ? $data['name'] : $this->name;
        $this->asciiName=(isset($data['asciiName'])) ? $data['asciiName'] : $this->asciiName;
        //$this->address=(isset($data['address'])) ? $data['address'] : $this->address;
        $this->website=(isset($data['website'])) ? $data['website'] : $this->website;
        $this->active=(isset($data['active'])) ? $data['active'] : $this->active;
        //$this->slogan=(isset($data['slogan'])) ? $data['slogan'] : $this->slogan;
        $this->email=(isset($data['email'])) ? $data['email'] : $this->email;
        //$this->logo=(isset($data['logo'])) ? $data['logo'] : $this->logo;
        $this->phone=(isset($data['phone'])) ? $data['phone'] : $this->phone;
        $this->description=(isset($data['description'])) ? $data['description'] : $this->description;
        //$this->jobs=(isset($data['jobs'])) ? $data['jobs'] : $this->jobs;
    }
}
