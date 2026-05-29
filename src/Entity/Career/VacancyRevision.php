<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisableInterface;
use App\Repository\Career\VacancyRevisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * An immutable snapshot of a {@see Vacancy}'s revisable content for one point in its revision chain. The stable
 * {@see Vacancy} owns the slug, publication flag, package and labels; everything that may be revised and reviewed
 * (the localised texts, the contact details and the category) lives here.
 */
#[Entity(repositoryClass: VacancyRevisionRepository::class)]
#[HasLifecycleCallbacks]
class VacancyRevision extends AbstractRevision
{
    /**
     * The vacancy this revision belongs to.
     */
    #[ManyToOne(
        targetEntity: Vacancy::class,
        inversedBy: 'revisions',
    )]
    #[JoinColumn(nullable: false)]
    private Vacancy $vacancy;

    /**
     * The revision this one supersedes (null for the first revision in the chain).
     */
    #[ManyToOne(targetEntity: self::class)]
    #[JoinColumn(nullable: true)]
    private ?VacancyRevision $previousRevision = null;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $name;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'location_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $location;

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
        name: 'attachment_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $attachment;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactName = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactPhone = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactEmail = null;

    #[ManyToOne(targetEntity: VacancyCategory::class)]
    #[JoinColumn(
        name: 'category_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private VacancyCategory $category;

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->vacancy;
    }

    public function getVacancy(): Vacancy
    {
        return $this->vacancy;
    }

    public function setVacancy(Vacancy $vacancy): void
    {
        $this->vacancy = $vacancy;
    }

    #[Override]
    public function getPreviousRevision(): ?VacancyRevision
    {
        return $this->previousRevision;
    }

    public function setPreviousRevision(?VacancyRevision $previousRevision): void
    {
        $this->previousRevision = $previousRevision;
    }

    public function getName(): CareerLocalisedText
    {
        return $this->name;
    }

    public function setName(CareerLocalisedText $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): CareerLocalisedText
    {
        return $this->location;
    }

    public function setLocation(CareerLocalisedText $location): void
    {
        $this->location = $location;
    }

    public function getWebsite(): CareerLocalisedText
    {
        return $this->website;
    }

    public function setWebsite(CareerLocalisedText $website): void
    {
        $this->website = $website;
    }

    public function getDescription(): CareerLocalisedText
    {
        return $this->description;
    }

    public function setDescription(CareerLocalisedText $description): void
    {
        $this->description = $description;
    }

    public function getAttachment(): CareerLocalisedText
    {
        return $this->attachment;
    }

    public function setAttachment(CareerLocalisedText $attachment): void
    {
        $this->attachment = $attachment;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getCategory(): VacancyCategory
    {
        return $this->category;
    }

    public function setCategory(VacancyCategory $category): void
    {
        $this->category = $category;
    }
}
