<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;


/**
 * CompanyPackage model.
 *
 * @ORM\Entity
 */
class CompanyPackage
{
    /**
     * The package's id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The package's starting date.
     *
     * @ORM\Column(type="date")
     */
    protected $starts;

    /**
     * The package's expiration date.
     *
     * @ORM\Column(type="date")
     */
    protected $expires;

    /**
     * The package's pusblish state.
     *
     * @ORM\Column(type="boolean")
     */
    protected $published;

    /**
     * The package's company.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="packages")
     */
    protected $company;

    /**
     * The package's jobs.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\Job", mappedBy="package")
     */
    protected $jobs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->jobs = new ArrayCollection();
    }

    /**
     * Get the package's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the package's starting date.
     *
     * @return date
     */
    public function getStartingDate()
    {
        return $this->starts;
    }

    /**
     * Set the package's starting date.
     *
     * @param date $starts
     */
    public function setStartingDate($starts)
    {
        $this->starts = $starts;
    }

    /**
     * Get the package's expiration date.
     *
     * @return date
     */
    public function getExpirationDate()
    {
        return $this->expires;
    }

    /**
     * Set the package's expiration date.
     *
     * @param date $expires
     */
    public function setExpirationDate($expires)
    {
        $this->expires = $expires;
    }

    /**
     * Get the package's publish state.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->published;
    }

    /**
     * Set the package's publish state.
     *
     * @param bool $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * Get the package's company.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the package's company.
     *
     * @param Company company
     *
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Get the jobs in the package.
     * 
     * @return array jobs in the package
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Adds a job to the package.
     * 
     * @param Job $job job to be added
     */
    public function addJob(Job $job)
    {
        $this->jobs->add($job);
    }

    /**
     * Removes a job from the package.
     * 
     * @param Job $job job to be removed
     */
    public function removeJob(Job $job)
    {
        $this->jobs->removeElement($job);
    }

    public function isExpired()
    {
        if(new \DateTime() > $this->getExpirationDate()){
            return true;
        }

        return false;
    }

    public function isActive()
    {
        if ($this->isExpired()) {
            // unpublish activity
            $this->setPublished(false);

            return false;
        }

        if (!$this->isPublished()) {
            return false;
        }

        return true;
    }

    // For zend2 forms
    public function getArrayCopy()
    {
        return ['id' => $this->id,
            'startDate' => $this->getStartingDate()->format('Y-m-d'),
            'expirationDate' => $this->getExpirationDate()->format('Y-m-d'),
            'published' => $this->isPublished(), ];
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : $this->getId();
        $this->setStartingDate((isset($data['startDate'])) ? new \DateTime($data['startDate']) : $this->getStartingDate());
        $this->setExpirationDate((isset($data['expirationDate'])) ? new \DateTime($data['expirationDate']) : $this->getExpirationDate());
        $this->setPublished((isset($data['published'])) ? $data['published'] : $this->isPublished());
    }
}
