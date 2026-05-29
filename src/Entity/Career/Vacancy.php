<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ as OrganModel;
use App\Repository\Career\VacancyRepository;
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
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Override;
use RuntimeException;

/**
 * Vacancy aggregate root.
 *
 * The stable identity, the slug, the publication flag, the owning package and the labels live here and survive across
 * edits. The revisable, reviewable content (localised texts, contact details and category) lives on the chain of
 * {@see VacancyRevision}s. The publicly live version is {@see self::getLiveRevision()} (the latest approved revision);
 * the working head is {@see self::getCurrentRevision()}.
 *
 * @psalm-import-type VacancyLabelArrayType from VacancyLabel as ImportedVacancyLabelArrayType
 */
#[Entity(repositoryClass: VacancyRepository::class)]
#[HasLifecycleCallbacks]
class Vacancy implements RevisableInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * The vacancy's slug name.
     */
    #[Column(type: Types::STRING)]
    private string $slugName;

    /**
     * The vacancy's status.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $published;

    /**
     * The vacancy's package.
     */
    #[ManyToOne(
        targetEntity: CompanyJobPackage::class,
        inversedBy: 'vacancies',
    )]
    #[JoinColumn(
        name: 'package_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CompanyJobPackage $package;

    /**
     * The full chain of revisions, newest first.
     *
     * @var Collection<array-key, VacancyRevision>
     */
    #[OneToMany(
        targetEntity: VacancyRevision::class,
        mappedBy: 'vacancy',
        cascade: ['persist'],
    )]
    #[OrderBy(['revisionNumber' => 'DESC'])]
    private Collection $revisions;

    /**
     * The working head of the chain (the most recent revision, regardless of state).
     */
    #[ManyToOne(targetEntity: VacancyRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?VacancyRevision $currentRevision = null;

    /**
     * The publicly live revision (the latest approved one), or null when nothing has been approved yet.
     */
    #[ManyToOne(targetEntity: VacancyRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?VacancyRevision $liveRevision = null;

    /**
     * Vacancy labels.
     *
     * @var Collection<array-key, VacancyLabel>
     */
    #[ManyToMany(
        targetEntity: VacancyLabel::class,
        inversedBy: 'vacancies',
        cascade: ['persist'],
    )]
    #[JoinTable(name: 'VacancyLabelAssignment')]
    private Collection $labels;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
        $this->labels = new ArrayCollection();
    }

    /**
     * @return Collection<array-key, VacancyRevision>
     */
    #[Override]
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(VacancyRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
        $revision->setVacancy($this);
    }

    #[Override]
    public function getCurrentRevision(): ?VacancyRevision
    {
        return $this->currentRevision;
    }

    public function setCurrentRevision(?VacancyRevision $currentRevision): void
    {
        $this->currentRevision = $currentRevision;
    }

    #[Override]
    public function getLiveRevision(): ?VacancyRevision
    {
        return $this->liveRevision;
    }

    public function setLiveRevision(?VacancyRevision $liveRevision): void
    {
        $this->liveRevision = $liveRevision;
    }

    #[Override]
    public function markRevisionLive(RevisionInterface $revision): void
    {
        if (!$revision instanceof VacancyRevision) {
            throw new RuntimeException('A vacancy can only be made live by one of its own revisions.');
        }

        $this->setLiveRevision($revision);
    }

    /**
     * The revision whose content is shown for this vacancy: the live (approved) one when present, otherwise the
     * working head. Only ever null for a vacancy with no revisions at all, which never occurs once persisted.
     */
    private function getDisplayRevision(): VacancyRevision
    {
        $revision = $this->liveRevision ?? $this->currentRevision;

        if (null === $revision) {
            throw new RuntimeException('Vacancy has no revision to display.');
        }

        return $revision;
    }

    /**
     * Get the vacancy's slug name.
     *
     * @return string the vacancy's slug name
     */
    public function getSlugName(): string
    {
        return $this->slugName;
    }

    /**
     * Set the vacancy's slug name.
     */
    public function setSlugName(string $name): void
    {
        $this->slugName = $name;
    }

    /**
     * Get the vacancy's status.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Set the vacancy's status.
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function isActive(): bool
    {
        return null !== $this->getLiveRevision()
            && $this->isPublished()
            && $this->getPackage()->isActive()
            && !$this->getPackage()->getCompany()->isHidden();
    }

    /**
     * Get the vacancy's package.
     */
    public function getPackage(): CompanyJobPackage
    {
        return $this->package;
    }

    public function setPackage(CompanyJobPackage $package): void
    {
        $this->package = $package;
    }

    /**
     * Get the vacancy's company.
     */
    public function getCompany(): Company
    {
        return $this->getPackage()->getCompany();
    }

    /**
     * Get the labels. Returns an array of VacancyLabelAssignments.
     *
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
        $label->addVacancy($this);
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
        $label->removeVacancy($this);
    }

    /**
     * Display proxy. Read paths (templates, views) keep reading content straight off the vacancy; it delegates to the
     * display revision (the live one when present, otherwise the working head).
     */
    public function getName(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getName();
    }

    /**
     * Returns the vacancy's location.
     *
     * The location property specifies for which location (i.e. city or country)
     * this vacancy is intended. This location may not be equal to the company's
     * address.
     */
    public function getLocation(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getLocation();
    }

    /**
     * Get the vacancy's website.
     */
    public function getWebsite(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getWebsite();
    }

    /**
     * Get the vacancy's description.
     */
    public function getDescription(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getDescription();
    }

    /**
     * Get the vacancy's attachment.
     */
    public function getAttachment(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getAttachment();
    }

    /**
     * Get the vacancy's contact's name.
     */
    public function getContactName(): ?string
    {
        return $this->getDisplayRevision()->getContactName();
    }

    /**
     * Get the vacancy's contact's phone.
     */
    public function getContactPhone(): ?string
    {
        return $this->getDisplayRevision()->getContactPhone();
    }

    /**
     * Get the vacancy's contact's email.
     */
    public function getContactEmail(): ?string
    {
        return $this->getDisplayRevision()->getContactEmail();
    }

    /**
     * Get the vacancy's category.
     */
    public function getCategory(): VacancyCategory
    {
        return $this->getDisplayRevision()->getCategory();
    }

    /**
     * Returns the string identifier of the Resource.
     */
    #[Override]
    public function getResourceId(): string
    {
        return 'vacancy';
    }

    /**
     * Vacancies are not owned by an organ.
     */
    #[Override]
    public function getResourceOrgan(): ?OrganModel
    {
        return null;
    }

    /**
     * Vacancies are owned by a company, not created by a member.
     */
    #[Override]
    public function getResourceCreator(): ?MemberModel
    {
        return null;
    }

    /**
     * The company that owns this vacancy (through its package), so the company's users can edit it.
     */
    #[Override]
    public function getResourceCompany(): ?Company
    {
        return $this->getPackage()->getCompany();
    }

    /**
     * @return array{
     *     slugName: string,
     *     category: VacancyCategory,
     *     contactName: ?string,
     *     contactEmail: ?string,
     *     contactPhone: ?string,
     *     published: bool,
     *     name: ?string,
     *     nameEn: ?string,
     *     location: ?string,
     *     locationEn: ?string,
     *     website: ?string,
     *     websiteEn: ?string,
     *     description: ?string,
     *     descriptionEn: ?string,
     *     attachment: ?string,
     *     attachmentEn: ?string,
     *     labels: ImportedVacancyLabelArrayType[],
     * }
     */
    public function toArray(): array
    {
        $labelsArrays = [];
        foreach ($this->getLabels() as $label) {
            $labelsArrays[] = $label->toArray();
        }

        return [
            'slugName' => $this->getSlugName(),
            'category' => $this->getCategory(),
            'contactName' => $this->getContactName(),
            'contactEmail' => $this->getContactEmail(),
            'contactPhone' => $this->getContactPhone(),
            'published' => $this->isPublished(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'location' => $this->getLocation()->getValueNL(),
            'locationEn' => $this->getLocation()->getValueEN(),
            'website' => $this->getWebsite()->getValueNL(),
            'websiteEn' => $this->getWebsite()->getValueEN(),
            'description' => $this->getDescription()->getValueNL(),
            'descriptionEn' => $this->getDescription()->getValueEN(),
            'attachment' => $this->getAttachment()->getValueNL(),
            'attachmentEn' => $this->getAttachment()->getValueEN(),
            'labels' => $labelsArrays,
        ];
    }
}
