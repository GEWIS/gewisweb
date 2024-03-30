<?php

declare(strict_types=1);

namespace Company\Model;

use Application\Model\Traits\IdentifiableTrait;
use Company\Model\Enums\CompanyPackageTypes;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Exception;

use function boolval;

/**
 * CompanyPackage model.
 */
#[Entity]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'packageType',
    type: 'string',
    enumType: CompanyPackageTypes::class,
)]
#[DiscriminatorMap(
    value: [
        'job' => CompanyJobPackage::class,
        'banner' => CompanyBannerPackage::class,
        'featured' => CompanyFeaturedPackage::class,
    ],
)]
abstract class CompanyPackage
{
    use IdentifiableTrait;

    /**
     * An alphanumeric strings which identifies to which contract this package belongs.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $contractNumber = null;

    /**
     * The package's starting date.
     */
    #[Column(type: 'date')]
    protected DateTime $starts;

    /**
     * The package's expiration date.
     */
    #[Column(type: 'date')]
    protected DateTime $expires;

    /**
     * The package's published state.
     */
    #[Column(type: 'boolean')]
    protected bool $published;

    /**
     * The package's company.
     */
    #[ManyToOne(
        targetEntity: Company::class,
        inversedBy: 'packages',
    )]
    protected Company $company;

    public function __construct()
    {
    }

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(?string $contractNumber): void
    {
        $this->contractNumber = $contractNumber;
    }

    /**
     * Get the package's starting date.
     */
    public function getStartingDate(): DateTime
    {
        return $this->starts;
    }

    /**
     * Set the package's starting date.
     */
    public function setStartingDate(DateTime $starts): void
    {
        $this->starts = $starts;
    }

    /**
     * Get the package's expiration date.
     */
    public function getExpirationDate(): DateTime
    {
        return $this->expires;
    }

    /**
     * Set the package's expiration date.
     */
    public function setExpirationDate(DateTime $expires): void
    {
        $this->expires = $expires;
    }

    /**
     * Get the package's publish state.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Set the package's publish state.
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get the package's company.
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * Set the package's company.
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Gets the type of the package.
     */
    abstract public function getType(): CompanyPackageTypes;

    /**
     * Check whether this package is expired.
     */
    public function isExpired(): bool
    {
        return (new DateTime()) >= $this->getExpirationDate();
    }

    public function isActive(): bool
    {
        $now = new DateTime();
        if ($this->isExpired()) {
            // unpublish activity
            $this->setPublished(false);

            return false;
        }

        return $now >= $this->getStartingDate() && $this->isPublished();
    }

    /**
     * @return array{
     *     contractNumber: ?string,
     *     startDate: string,
     *     expirationDate: string,
     *     published: bool,
     * }
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
     * @psalm-param array{
     *     contractNumber: ?string,
     *     startDate?: string,
     *     expirationDate?: string,
     *     published?: bool|string,
     * } $data
     *
     * @throws Exception
     */
    public function exchangeArray(array $data): void
    {
        $this->setContractNumber($data['contractNumber']);
        $this->setStartingDate(
            isset($data['startDate']) ? new DateTime($data['startDate']) : $this->getStartingDate(),
        );
        $this->setExpirationDate(
            isset($data['expirationDate']) ? new DateTime($data['expirationDate']) : $this->getExpirationDate(),
        );
        $this->setPublished(isset($data['published']) ? boolval($data['published']) : $this->isPublished());
    }
}
