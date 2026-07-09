<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisableInterface;
use App\Repository\Career\CompanyRevisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * An immutable snapshot of a {@see Company}'s revisable content for one point in its revision chain. The stable
 * {@see Company} owns its name, slug, representative details, packages and publication flag; everything that may be
 * revised and reviewed (the localised texts, the logo and the contact details) lives here.
 */
#[Entity(repositoryClass: CompanyRevisionRepository::class)]
#[HasLifecycleCallbacks]
class CompanyRevision extends AbstractRevision
{
    /**
     * The company this revision belongs to.
     */
    #[ManyToOne(
        targetEntity: Company::class,
        inversedBy: 'revisions',
    )]
    #[JoinColumn(nullable: false)]
    private Company $company;

    /**
     * The revision this one supersedes (null for the first revision in the chain).
     */
    #[ManyToOne(targetEntity: self::class)]
    #[JoinColumn(nullable: true)]
    private ?CompanyRevision $previousRevision = null;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'slogan_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $slogan;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'description_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $description;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'website_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $website;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $logo = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactName = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactAddress = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactEmail = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactPhone = null;

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->company;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    #[Override]
    public function getPreviousRevision(): ?CompanyRevision
    {
        return $this->previousRevision;
    }

    public function setPreviousRevision(?CompanyRevision $previousRevision): void
    {
        $this->previousRevision = $previousRevision;
    }

    public function getSlogan(): CareerLocalisedText
    {
        return $this->slogan;
    }

    public function setSlogan(CareerLocalisedText $slogan): void
    {
        $this->slogan = $slogan;
    }

    public function getDescription(): CareerLocalisedText
    {
        return $this->description;
    }

    public function setDescription(CareerLocalisedText $description): void
    {
        $this->description = $description;
    }

    public function getWebsite(): CareerLocalisedText
    {
        return $this->website;
    }

    public function setWebsite(CareerLocalisedText $website): void
    {
        $this->website = $website;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?string $contactAddress): void
    {
        $this->contactAddress = $contactAddress;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }
}
