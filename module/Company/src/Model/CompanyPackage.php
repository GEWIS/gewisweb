<?php

namespace Company\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    DiscriminatorColumn,
    DiscriminatorMap,
    Entity,
    GeneratedValue,
    Id,
    InheritanceType,
    ManyToOne,
};
use Exception;

/**
 * CompanyPackage model.
 */
#[Entity]
#[InheritanceType(value: "SINGLE_TABLE")]
#[DiscriminatorColumn(
    name: "packageType",
    type: "string",
)]
#[DiscriminatorMap(value:
    [
        "job" => CompanyJobPackage::class,
        "banner" => CompanyBannerPackage::class,
        "featured" => CompanyFeaturedPackage::class,
    ]
)]
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
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * The package's starting date.
     */
    #[Column(type: "date")]
    protected DateTime $starts;

    /**
     * The package's expiration date.
     */
    #[Column(type: "date")]
    protected DateTime $expires;

    /**
     * The package's pusblish state.
     */
    #[Column(type: "boolean")]
    protected bool $published;

    /**
     * The package's company.
     */
    #[ManyToOne(
        targetEntity: Company::class,
        inversedBy: "packages",
    )]
    protected Company $company;

    /**
     * Get the package's id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the package's starting date.
     *
     * @return DateTime
     */
    public function getStartingDate(): DateTime
    {
        return $this->starts;
    }

    /**
     * Set the package's starting date.
     *
     * @param DateTime $starts
     */
    public function setStartingDate(DateTime $starts): void
    {
        $this->starts = $starts;
    }

    /**
     * Get the package's expiration date.
     *
     * @return DateTime
     */
    public function getExpirationDate(): DateTime
    {
        return $this->expires;
    }

    /**
     * Set the package's expiration date.
     *
     * @param DateTime $expires
     */
    public function setExpirationDate(DateTime $expires): void
    {
        $this->expires = $expires;
    }

    /**
     * Get the package's publish state.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Get the number of jobs in the package.
     * This method can be overridden in subclasses.
     *
     * @param $category
     *
     * @return integer 0
     */
    public function getNumberOfActiveJobs($category): int
    {
        return 0;
    }

    /**
     * Set the package's publish state.
     *
     * @param bool $published
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get the package's company.
     *
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * Set the package's company.
     *
     * @param Company $company
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Get's the type of the package.
     *
     * @return string
     */
    public function getType(): string
    {
        switch (get_class($this)) {
            case "Company\Model\CompanyBannerPackage":
                return 'banner';
            case "Company\Model\CompanyJobPackage":
                return 'job';
            case "Company\Model\CompanyFeaturedPackage":
                return 'featured';
        }
    }

    /**
     * @param DateTime $now
     *
     * @return bool
     */
    public function isExpired(DateTime $now): bool
    {
        if ($now > $this->getExpirationDate()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
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
    /**
     * @return array
     */
    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'startDate' => $this->getStartingDate()->format('Y-m-d'),
            'expirationDate' => $this->getExpirationDate()->format('Y-m-d'),
            'published' => $this->isPublished(),
        ];
    }

    /**
     * @param array $data
     *
     * @throws Exception
     */
    public function exchangeArray(array $data): void
    {
        $this->id = (isset($data['id'])) ? $data['id'] : $this->getId();
        $this->setStartingDate(
            (isset($data['startDate'])) ? new DateTime($data['startDate']) : $this->getStartingDate()
        );
        $this->setExpirationDate(
            (isset($data['expirationDate'])) ? new DateTime($data['expirationDate']) : $this->getExpirationDate()
        );
        $this->setPublished((isset($data['published'])) ? $data['published'] : $this->isPublished());
    }
}
