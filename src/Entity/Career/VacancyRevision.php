<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisableInterface;
use App\Entity\Career\Enums\VacancyCategories;
use App\Repository\Career\VacancyRevisionRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * An immutable snapshot of a {@see Vacancy}'s revisable content for one point in its revision chain. The stable
 * {@see Vacancy} owns the slug, publication flag and package; everything that may be revised and reviewed (the
 * localised texts, the contact details, the category and the labels) lives here, so label changes go through the
 * review workflow like the rest of the content.
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

    /**
     * Which of the four kinds of posting this is. Jobs until somebody says otherwise, so a blank revision is complete
     * enough for a form to bind to.
     */
    #[Column(
        type: Types::STRING,
        enumType: VacancyCategories::class,
    )]
    private VacancyCategories $category = VacancyCategories::Jobs;

    /**
     * The day the vacancy starts being shown, or null to show it from the moment it is approved.
     */
    #[Column(
        type: Types::DATE_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $startDate = null;

    /**
     * The last day the vacancy is shown. Required: a company knows when applications close before it knows anything
     * else, and a posting nobody has to put an end to is one that quietly goes stale. The owning job package caps it
     * regardless, since a vacancy cannot outlive the contract it was sold under.
     *
     * The window is part of the reviewed content, so moving it goes past the committee like anything else.
     *
     * PHP-nullable so a not-yet-filled draft renders an empty field; the column stays NOT NULL and the form's NotBlank
     * constraint guarantees a value before persist, so a saved revision always has a closing day.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private ?DateTime $endDate = null;

    /**
     * The labels of this revision of the vacancy. Each revision owns its own assignments (carried forward when a draft
     * is cloned), so label changes are staged with the revision and only become public on approval.
     *
     * @var Collection<array-key, VacancyLabel>
     */
    #[ManyToMany(
        targetEntity: VacancyLabel::class,
        inversedBy: 'revisions',
        cascade: ['persist'],
    )]
    #[JoinTable(name: 'VacancyRevisionLabelAssignment')]
    private Collection $labels;

    public function __construct()
    {
        $this->labels = new ArrayCollection();

        // Which localised texts a revision has is its own business, and a form cannot bind to one that has none.
        // Doctrine does not run this when it hydrates a stored revision, so nothing is thrown away.
        $this->name = new CareerLocalisedText(
            null,
            null,
        );
        $this->location = new CareerLocalisedText(
            null,
            null,
        );
        $this->website = new CareerLocalisedText(
            null,
            null,
        );
        $this->description = new CareerLocalisedText(
            null,
            null,
        );
        $this->attachment = new CareerLocalisedText(
            null,
            null,
        );
    }

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->vacancy;
    }

    /**
     * @return class-string<AbstractRevisionComment>
     */
    #[Override]
    public function getCommentClass(): string
    {
        return VacancyRevisionComment::class;
    }

    public function getVacancy(): Vacancy
    {
        return $this->vacancy;
    }

    public function setVacancy(Vacancy $vacancy): void
    {
        $this->vacancy = $vacancy;
    }

    /**
     * @return Collection<array-key, VacancyLabel>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    /**
     * @param VacancyLabel[] $labels
     */
    public function addLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->addLabel($label);
        }
    }

    public function addLabel(VacancyLabel $label): void
    {
        if ($this->labels->contains($label)) {
            return;
        }

        $this->labels->add($label);
        $label->addRevision($this);
    }

    /**
     * @param VacancyLabel[] $labels
     */
    public function removeLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->removeLabel($label);
        }
    }

    public function removeLabel(VacancyLabel $label): void
    {
        if (!$this->labels->contains($label)) {
            return;
        }

        $this->labels->removeElement($label);
        $label->removeRevision($this);
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

    public function getCategory(): VacancyCategories
    {
        return $this->category;
    }

    public function setCategory(VacancyCategories $category): void
    {
        $this->category = $category;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * Whether today falls inside the posting window. The last day counts. A revision without a closing day has not
     * been saved yet, so there is nothing to have fallen outside of.
     */
    public function isWithinPostingWindow(): bool
    {
        $today = new DateTime('today');

        if (
            null !== $this->startDate
            && $today < $this->startDate
        ) {
            return false;
        }

        return null === $this->endDate
            || $today <= $this->endDate;
    }
}
