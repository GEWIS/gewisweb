<?php

namespace Company\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    DiscriminatorColumn,
    DiscriminatorMap,
    Entity,
    InheritanceType,
    ManyToOne,
};
use Doctrine\Common\Util\ClassUtils;
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
#[DiscriminatorMap(
    value: [
        "job" => CompanyJobPackage::class,
        "banner" => CompanyBannerPackage::class,
        "featured" => CompanyFeaturedPackage::class,
    ],
)]
abstract class CompanyPackage
{
    use IdentifiableTrait;

    /**
     * An alphanumeric strings which identifies to which contract this package belongs.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contractNumber = null;

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

    public function __construct()
    {
    }

    /**
     * @return string|null
     */
    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    /**
     * @param string|null $contractNumber
     */
    public function setContractNumber(?string $contractNumber): void
    {
        $this->contractNumber = $contractNumber;
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
     * Gets the type of the package.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getType(): string
    {
        return match (ClassUtils::getClass($this)) {
            CompanyBannerPackage::class => 'banner',
            CompanyJobPackage::class => 'job',
            CompanyFeaturedPackage::class => 'featured',
            default => throw new Exception('Unknown type for class that extends CompanyPackage'),
        };
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

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'contractNumber' => $this->getContractNumber(),
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
        $this->setContractNumber($data['contractNumber']);
        $this->setStartingDate(
            (isset($data['startDate'])) ? new DateTime($data['startDate']) : $this->getStartingDate()
        );
        $this->setExpirationDate(
            (isset($data['expirationDate'])) ? new DateTime($data['expirationDate']) : $this->getExpirationDate()
        );
        $this->setPublished((isset($data['published'])) ? $data['published'] : $this->isPublished());
    }
}
