<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

/**
 * CompanyPacket model.
 *
 * @ORM\Entity
 */
class CompanyPacket
{
    /**
     * The packet's id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The packet's starting date.
     *
     * @ORM\Column(type="date")
     */
    protected $starts;

    /**
     * The packet's expiration date.
     *
     * @ORM\Column(type="date")
     */
    protected $expires;

    /**
     * The packet's pusblish state.
     *
     * @ORM\Column(type="boolean")
     */
    protected $published;

    /**
     * The packet's company.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="packets")
     */
    protected $company;

    /**
     * The packet's jobs.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\Job", mappedBy="packet")
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
     * Get the packet's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the packet's starting date.
     *
     * @return date
     */
    public function getStartingDate()
    {
        return $this->starts;
    }

    /**
     * Set the packet's starting date.
     *
     * @param date $starts
     */
    public function setStartingDate($starts)
    {
        $this->starts = $starts;
    }

    /**
     * Get the packet's expiration date.
     *
     * @return date
     */
    public function getExpirationDate()
    {
        return $this->expires;
    }

    /**
     * Set the packet's expiration date.
     *
     * @param date $expires
     */
    public function setExpirationDate($expires)
    {
        $this->expires = $expires;
    }

    /**
     * Get the packet's publish state.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->published;
    }

    /**
     * Set the packet's publish state.
     *
     * @param bool $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * Get the packet's company.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the packet's company.
     *
     * @param Company company
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Get the jobs in the packet.
     * 
     * @return array jobs in the packet
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Adds a job to the packet.
     * 
     * @param Job $job job to be added
     */
    public function addJob(Job $job)
    {
        $this->jobs->add($job);
    }

    /**
     * Removes a job from the packet.
     * 
     * @param Job $job job to be removed
     */
    public function removeJob(Job $job)
    {
        $this->jobs->removeElement($job);
    }

    public function isExpired()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentDay = date('d');

        if ($currentYear > $this->getExpirationDate()->format('Y')) {
            return true;
        }
        if ($currentMonth > $this->getExpirationDate()->format('m') and $currentYear == $this->getExpirationDate()->format('Y')) {
            return true;
        }
        if ($currentDay > $this->getExpirationDate()->format('d')  and $currentMonth == $this->getExpirationDate()->format('m') and $currentYear == $this->getExpirationDate()->format('Y')) {
            return true;
        }

        if ($currentYear < $this->getStartingDate()->format('Y')) {
            return true;
        }
        if ($currentMonth < $this->getStartingDate()->format('m') and $currentYear == $this->getStartingDate()->format('Y')) {
            return true;
        }
        if ($currentDay < $this->getStartingDate()->format('d')  and $currentMonth == $this->getStartingDate()->format('m') and $currentYear == $this->getStartingDate()->format('Y')) {
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
        $this->id = (isset($data['published'])) ? $data['id'] : $this->id();
        $this->setStartingDate((isset($data['startDate'])) ? new \DateTime($data['startDate']) : $this->getStartingDate());
        $this->setExpirationDate((isset($data['expirationDate'])) ? new \DateTime($data['expirationDate']) : $this->getExpirationDate());
        $this->setPublished((isset($data['published'])) ? $data['published'] : $this->isPublished());
    }
}
