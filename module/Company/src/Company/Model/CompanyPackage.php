<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;


/**
 * CompanyPackage model.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="packageType",type="string")
 * @ORM\DiscriminatorMap({"job"="CompanyJobPackage","banner"="CompanyBannerPackage","featured"="CompanyFeaturedPackage"})
 */
abstract class CompanyPackage
{
    /**
     * Constructor.
     */
    public function __construct()
    {

    }

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
     * Get number of active jobs.
     *
     * @return returns 0
     */
    public function getNumberOfActiveJobs()
    {
        return 0;
    }

    /**
     * Get the number of jobs in the package.
     *
     * @return returns 0
     */
    public function getNumberOfActiveJobs()
    {
        return 0;
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
     * Get's the type of the package
     *
     */
    public function getType()
    {
        switch (get_class($this)) {
            case "Company\Model\CompanyBannerPackage":
                return "banner";
            case "Company\Model\CompanyJobPackage":
                return "job";
            case "Company\Model\CompanyFeaturedPackage":
                return "featured";
        }

    }


    public function isExpired($now)
    {
        if ($now > $this->getExpirationDate()) {
            return true;
        }

        return false;
    }

    public function isActive()
    {
        $now = new \DateTime();
        if ($this->isExpired($now)) {
            // unpublish activity
            $this->setPublished(false);

            return false;
        }

        if ($now < $this->getStartingDate()) {
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
