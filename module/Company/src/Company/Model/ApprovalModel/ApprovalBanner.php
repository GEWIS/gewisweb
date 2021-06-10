<?php


namespace Company\Model\ApprovalModel;


use Company\Model\ApprovalModel\ApprovalAbstract;
use Doctrine\ORM\Mapping as ORM;
use Company\Model\ApprovalModel\Company;

/**
 * VacancyApproval model.
 *
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="packageType",type="string")
 * @ORM\DiscriminatorMap({"job"="Company\Model\CompanyJobPackage","banner"="Company\Model\CompanyBannerPackage","featured"="Company\Model\CompanyFeaturedPackage", "highlight"="Company\Model\CompanyHighlightPackage"})
 *
 */
class ApprovalBanner implements ApprovalAbstract
{

    /**
     * The profile approvals company
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="translations", cascade={"persist"})
     */
    protected $company;

    /**
     * The profile approvals approved status
     *
     * @ORM\Column(type="boolean")
     */
    protected $rejected = false;


    // TODO add other profile variables
    /**
     * The company id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @ORM\OneToOne(targetEntity="User\Model\Company")
     */
    protected $id;

    /**
     * The package's starting date.
     *
     * @ORM\Column(type="date")
     */
    protected $starts;

    /**s
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
     * The package's contractNumber
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $contractNumber;

    /**
     * @param mixed $contractNumber
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getRejected()
    {
        return $this->rejected;
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
     * Get the number of jobs in the package.
     * This method can be overridden in subclasses
     *
     * @return returns 0
     */
    public function getNumberOfActiveJobs($category)
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
            case "Company\Model\CompanyHighlightPackage":
                return "highlight";
        }
    }

    /**
     * Get the package's contract number.
     *
     * @return int
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
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
        $now = new DateTime();
        if ($this->isExpired($now)) {
            // unpublish activity
            $this->setPublished(false);

            return false;
        }

        if ($now < $this->getStartingDate() || !$this->isPublished()) {
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
            'published' => $this->isPublished(),
            'contractNumber' => $this->getContractNumber()];
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : $this->getId();
        $this->setStartingDate((isset($data['startDate'])) ? new DateTime($data['startDate']) : $this->getStartingDate());
        $this->setExpirationDate((isset($data['expirationDate'])) ? new DateTime($data['expirationDate']) : $this->getExpirationDate());
        $this->setContractNumber((isset($data['contractNumber'])) ? ($data['contractNumber']) : $this->getContractNumber());
        $this->setPublished((isset($data['published'])) ? $data['published'] : $this->isPublished());
    }
}
