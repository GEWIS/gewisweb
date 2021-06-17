<?php

namespace Company\Model;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

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
    protected $active = false;

    /**
     * The job's website.
     *
     * @ORM\Column(type="string")
     */
    protected $website;

    /**
     * The location(url) of an attachment describing the job.
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
     * The job's location.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $location;

    /**
     * The job's timestamp.
     *
     * @ORM\Column(type="date")
     */
    protected $timestamp;


    /**
     * The job's description.
     *
     * @ORM\Column(type="text")
     */
    protected $teaser;

    /**
     * @return mixed
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * @param mixed $teaser
     */
    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    /**
     * The job's start date.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $startingDate;

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
     * The job's category.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\JobCategory")
     */
    protected $category;

    /**
     * The category id.
     *
     * @ORM\Column(type="integer")
     */
    protected $languageNeutralId;

    /**
     * Job labels
     *
     * @ORM\OneToMany(targetEntity="Company\Model\JobLabelAssignment", mappedBy="job", cascade={"persist", "remove"}, fetch="EAGER")
     */
    protected $labels;

    /**
     * The type of hours.
     *
     * @ORM\Column(type="string")
     */
    protected $hours;

    /**
     * The job's category.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\JobSector")@ORM\ManyToOne(targetEntity="\Company\Model\JobSector")
     */
    protected $sectors;



    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->labels = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getStartingDate()
    {
        return $this->startingDate;
    }

    /**
     * @param mixed $startingDate
     */
    public function setStartingDate($startingDate)
    {
        $this->startingDate = $startingDate;
    }



    /**
     * @return mixed
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * @param mixed $hours
     */
    public function setHours($hours)
    {
        $this->hours = $hours;
    }


    /**
     * @return mixed
     */
    public function getSectors()
    {
        return $this->sectors;
    }

    public function findSectorsById() {

    }

    /**
     * @param mixed $sectors
     */
    public function setSectors($sectors)
    {
        $this->sectors = $sectors;
    }


    /**
     * Get's the id
     */
    public function getLanguageNeutralId()
    {
        $lnid = $this->languageNeutralId;
        if ($lnid == 0) {
            return $this->id;
        }

        return $lnid;
    }

    /**
     * Set's the id
     */
    public function setLanguageNeutralId($languageNeutralId)
    {
        $this->languageNeutralId = $languageNeutralId;
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
     * Get the job's category.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set the job's category.
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
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
        return $this->getActive() && $this->getPackage()->isActive() && !$this->getPackage()->getCompany()->isHidden();
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
     * @return Carbon
     */
    public function getTimestamp()
    {
        if (is_null($this->timestamp)) {
            return null;
        }

        return Carbon::instance($this->timestamp);
    }

    /**
     * Set the job's timestamp.
     *
     * @param DateTime $timestamp
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
     * Get the labels. Returns an array of JobLabelAssignments
     *
     * @return array
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * Sets all labels.
     *
     * @param array $labels
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;
    }

    /**
     * Adds a label.
     *
     * @param JobLabelAssignment $label
     */
    public function addLabel($label)
    {
        if ($this->labels === null) {
            $this->labels = [];
        }
        $label->setJob($this);
        $this->labels[] = $label;
    }

    public function setPackage(CompanyPackage $package)
    {
        $this->package = $package;
    }

    /**
     * Returns the job's location
     *
     * The location property specifies for which location (i.e. city or country)
     * this job is intended. This location may not be equal to the company's
     * address.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the job's location
     *
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
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
     * Updates this object with values in the form of getArrayCopy()
     *
     */
    public function exchangeArray($data)
    {
        $this->setHours($this->updateIfSet($data['hours'], null));
        $this->setStartingDate($this->updateIfSet(new \DateTime($data['startingDate']), null));
        $this->setContactName($this->updateIfSet($data['contactName'], ''));
        $this->setPhone($this->updateIfSet($data['phone'], ''));
        $this->setEmail($this->updateIfSet($data['email'], ''));
        $this->setWebsite($this->updateIfSet($data['website'], 0));
        $this->setLocation($this->updateIfSet($data['location'], ''));
    }

    /**
     * Updates this object with values in the form of getArrayCopy()
     *
     */
    public function exchangeLanguageArray($data)
    {
        $this->setName($this->updateIfSet($data['name'], ''));
        $this->setSlugName($this->updateIfSet($data['slugName'], ''));
        $this->setDescription($this->updateIfSet($data['description'], ''));
        $this->setTeaser($this->updateIfSet($data['teaser'], ''));
        $this->setActive($this->updateIfSet($data['active'], null));
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
        $arraycopy['category'] = $this->getCategory();
        $arraycopy['contactName'] = $this->getContactName();
        $arraycopy['email'] = $this->getEmail();
        $arraycopy['phone'] = $this->getPhone();
        $arraycopy['sectors'] = $this->getSectors();
        $arraycopy['hours'] = $this->getHours();
        $arraycopy['startingDate'] = $this->getStartingDate();
        $arraycopy['website'] = $this->getWebsite();
        $arraycopy['location'] = $this->getLocation();

        return $arraycopy;
    }

}
