<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Application\LocalisedText;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Career\Company as CompanyModel;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ as OrganModel;
use App\Repository\Activity\ActivityRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Override;
use RuntimeException;

use function assert;

/**
 * Activity aggregate root.
 *
 * The stable identity, the sign-up graph (sign-up lists and their sign-ups), the organising party, the creator and
 * the labels live here and survive across edits. The revisable, reviewable content (localised texts, schedule,
 * category, facility flags) lives on the chain of {@see ActivityRevision}s. The publicly live version is
 * {@see self::getLiveRevision()} (the latest approved revision); the working head is {@see self::getCurrentRevision()}.
 *
 * @psalm-import-type ActivityLabelArrayType from ActivityLabel as ImportedActivityLabelArrayType
 * @psalm-import-type SignupListArrayType from SignupList as ImportedSignupListArrayType
 * @psalm-type ActivityArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 *     beginTime: DateTime,
 *     endTime: DateTime,
 *     location: ?string,
 *     locationEn: ?string,
 *     costs: ?string,
 *     costsEn: ?string,
 *     description: ?string,
 *     descriptionEn: ?string,
 *     organ: ?OrganModel,
 *     company: ?CompanyModel,
 *     category: string,
 *     requireGEFLITST: bool,
 *     requireZettle: bool,
 *     labels: ImportedActivityLabelArrayType[],
 *     signupLists: ImportedSignupListArrayType[],
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedText as ImportedLocalisedTextGdprArrayType
 * @psalm-import-type ActivityLabelGdprArrayType from ActivityLabel as ImportedActivityLabelGdprArrayType
 * @psalm-import-type SignupListGdprArrayType from SignupList as ImportedSignupListGdprArrayType
 * @psalm-type ActivityGdprArrayType = array{
 *     id: int,
 *     name: ImportedLocalisedTextGdprArrayType,
 *     beginTime: string,
 *     endTime: string,
 *     location: ImportedLocalisedTextGdprArrayType,
 *     costs: ImportedLocalisedTextGdprArrayType,
 *     description: ImportedLocalisedTextGdprArrayType,
 *     organ: ?int,
 *     company: ?int,
 *     category: string,
 *     requireGEFLITST: bool,
 *     requireZettle: bool,
 *     labels: ImportedActivityLabelGdprArrayType[],
 *     signupLists: ImportedSignupListGdprArrayType[],
 * }
 */
#[Entity(repositoryClass: ActivityRepository::class)]
class Activity implements RevisableInterface
{
    use IdentifiableTrait;

    /**
     * Who created this activity.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $creator;

    /**
     * The full chain of revisions, newest first.
     *
     * @var Collection<array-key, ActivityRevision>
     */
    #[OneToMany(
        targetEntity: ActivityRevision::class,
        mappedBy: 'activity',
        cascade: ['persist'],
    )]
    #[OrderBy(['revisionNumber' => 'DESC'])]
    private Collection $revisions;

    /**
     * The working head of the chain (the most recent revision, regardless of state).
     */
    #[ManyToOne(targetEntity: ActivityRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?ActivityRevision $currentRevision = null;

    /**
     * The publicly live revision (the latest approved one), or null when nothing has been approved yet.
     */
    #[ManyToOne(targetEntity: ActivityRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?ActivityRevision $liveRevision = null;

    /**
     * All additional Labels belonging to this activity.
     *
     * @var Collection<array-key, ActivityLabel>
     */
    #[ManyToMany(
        targetEntity: ActivityLabel::class,
        inversedBy: 'activities',
        cascade: ['persist'],
    )]
    #[JoinTable(name: 'ActivityLabelAssignment')]
    private Collection $labels;

    /**
     * All additional SignupLists belonging to this activity.
     *
     * Deliberately no `cascade: ['remove']` / `orphanRemoval`: sign-up lists (and the sign-ups hanging off them) must
     * survive across revisions and must never be cascade-deleted when content is revised.
     *
     * @var Collection<array-key, SignupList>
     */
    #[OneToMany(
        targetEntity: SignupList::class,
        mappedBy: 'activity',
    )]
    #[OrderBy([
        'promoted' => 'DESC',
        'id' => 'ASC',
    ])]
    private Collection $signupLists;

    /**
     * Which organ organises this activity.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?OrganModel $organ = null;

    /**
     * Which company organises this activity.
     */
    #[ManyToOne(targetEntity: CompanyModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?CompanyModel $company = null;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->signupLists = new ArrayCollection();
    }

    /**
     * @return Collection<array-key, ActivityRevision>
     */
    #[Override]
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(ActivityRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
        $revision->setActivity($this);
    }

    #[Override]
    public function getCurrentRevision(): ?ActivityRevision
    {
        return $this->currentRevision;
    }

    public function setCurrentRevision(?ActivityRevision $currentRevision): void
    {
        $this->currentRevision = $currentRevision;
    }

    #[Override]
    public function getLiveRevision(): ?ActivityRevision
    {
        return $this->liveRevision;
    }

    public function setLiveRevision(?ActivityRevision $liveRevision): void
    {
        $this->liveRevision = $liveRevision;
    }

    #[Override]
    public function markRevisionLive(RevisionInterface $revision): void
    {
        if (!$revision instanceof ActivityRevision) {
            throw new RuntimeException('An activity can only be made live by one of its own revisions.');
        }

        $this->setLiveRevision($revision);
    }

    /**
     * The revision whose content is shown for this activity: the live (approved) one when present, otherwise the
     * working head. Only ever null for an activity with no revisions at all, which never occurs once persisted.
     */
    public function getDisplayRevision(): ActivityRevision
    {
        $revision = $this->liveRevision ?? $this->currentRevision;

        if (null === $revision) {
            throw new RuntimeException('Activity has no revision to display.');
        }

        return $revision;
    }

    /**
     * @param ActivityLabel[] $labels
     */
    public function addLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->addLabel($label);
        }
    }

    public function addLabel(ActivityLabel $label): void
    {
        if ($this->labels->contains($label)) {
            return;
        }

        $this->labels->add($label);
        $label->addActivity($this);
    }

    /**
     * @param ActivityLabel[] $labels
     */
    public function removeLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->removeLabel($label);
        }
    }

    public function removeLabel(ActivityLabel $label): void
    {
        if (!$this->labels->contains($label)) {
            return;
        }

        $this->labels->removeElement($label);
        $label->removeActivity($this);
    }

    /**
     * Adds SignupLists to this activity.
     *
     * @param SignupList[] $signupLists
     */
    public function addSignupLists(array $signupLists): void
    {
        foreach ($signupLists as $signupList) {
            $this->addSignupList($signupList);
        }
    }

    public function addSignupList(SignupList $signupList): void
    {
        if ($this->signupLists->contains($signupList)) {
            return;
        }

        $this->signupLists->add($signupList);
        $signupList->setActivity($this);
    }

    /**
     * Removes SignupLists from this activity.
     *
     * @param SignupList[] $signupLists
     */
    public function removeSignupLists(array $signupLists): void
    {
        foreach ($signupLists as $signupList) {
            $this->removeSignupList($signupList);
        }
    }

    public function removeSignupList(SignupList $signupList): void
    {
        if (!$this->signupLists->contains($signupList)) {
            return;
        }

        $this->signupLists->removeElement($signupList);
    }

    /**
     * Returns a Collection of SignupLists associated with this activity.
     *
     * @return Collection<array-key, SignupList>
     */
    public function getSignupLists(): Collection
    {
        return $this->signupLists;
    }

    /**
     * The next sign-up list whose deadline is relevant to surface on overviews (see GH-2082): among the lists that have
     * not yet closed, the currently-open one closing soonest, otherwise the one opening soonest. Null when all closed.
     */
    public function getRelevantSignupList(): ?SignupList
    {
        $now = new DateTime('now');
        $open = null;
        $upcoming = null;

        foreach ($this->signupLists as $signupList) {
            if ($signupList->getCloseDate() <= $now) {
                continue;
            }

            if ($signupList->getOpenDate() <= $now) {
                if (
                    null === $open
                    || $signupList->getCloseDate() < $open->getCloseDate()
                ) {
                    $open = $signupList;
                }
            } elseif (
                null === $upcoming
                || $signupList->getOpenDate() < $upcoming->getOpenDate()
            ) {
                $upcoming = $signupList;
            }
        }

        return $open ?? $upcoming;
    }

    /**
     * The number of sign-up lists that have not yet closed, i.e. that still have a relevant deadline.
     */
    public function countPendingSignupLists(): int
    {
        $now = new DateTime('now');
        $count = 0;

        foreach ($this->signupLists as $signupList) {
            if ($signupList->getCloseDate() <= $now) {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    /**
     * @return Collection<array-key, ActivityLabel>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function getCreator(): MemberModel
    {
        return $this->creator;
    }

    public function setCreator(MemberModel $creator): void
    {
        $this->creator = $creator;
    }

    public function getOrgan(): ?OrganModel
    {
        return $this->organ;
    }

    public function setOrgan(?OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    public function getCompany(): ?CompanyModel
    {
        return $this->company;
    }

    public function setCompany(?CompanyModel $company): void
    {
        $this->company = $company;
    }

    /**
     * Display proxy. Read paths (templates, views, GDPR export) keep reading content straight off the activity; it
     * delegates to the display revision (the live one when present, otherwise the working head).
     */
    public function getName(): ActivityLocalisedText
    {
        return $this->getDisplayRevision()->getName();
    }

    public function getLocation(): ActivityLocalisedText
    {
        return $this->getDisplayRevision()->getLocation();
    }

    public function getCosts(): ActivityLocalisedText
    {
        return $this->getDisplayRevision()->getCosts();
    }

    public function getDescription(): ActivityLocalisedText
    {
        return $this->getDisplayRevision()->getDescription();
    }

    public function getBeginTime(): DateTime
    {
        // A displayed revision is always persisted, and the form's NotBlank constraint guarantees a schedule, so this
        // is never null in practice; the revision getter is only nullable to let a brand-new draft render empty fields.
        $beginTime = $this->getDisplayRevision()->getBeginTime();
        assert(null !== $beginTime);

        return $beginTime;
    }

    public function getEndTime(): DateTime
    {
        $endTime = $this->getDisplayRevision()->getEndTime();
        assert(null !== $endTime);

        return $endTime;
    }

    public function getCategory(): ActivityCategories
    {
        return $this->getDisplayRevision()->getCategory();
    }

    public function getRequireGEFLITST(): bool
    {
        return $this->getDisplayRevision()->getRequireGEFLITST();
    }

    public function getRequireZettle(): bool
    {
        return $this->getDisplayRevision()->getRequireZettle();
    }

    /**
     * Returns the string identifier of the Resource.
     */
    #[Override]
    public function getResourceId(): string
    {
        return 'activity';
    }

    /**
     * Get the organ of this resource.
     */
    #[Override]
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getOrgan();
    }

    /**
     * Activities are owned by their creator/organ, not by a company, so company-scoped editing never applies.
     */
    #[Override]
    public function getResourceCompany(): ?CompanyModel
    {
        return null;
    }

    /**
     * Get the creator of this resource.
     */
    #[Override]
    public function getResourceCreator(): MemberModel
    {
        return $this->getCreator();
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return ActivityArrayType
     */
    public function toArray(): array
    {
        $signupListsArrays = [];
        foreach ($this->getSignupLists() as $signupList) {
            $signupListsArrays[] = $signupList->toArray();
        }

        $labelsArrays = [];
        foreach ($this->getLabels() as $label) {
            $labelsArrays[] = $label->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'beginTime' => $this->getBeginTime(),
            'endTime' => $this->getEndTime(),
            'location' => $this->getLocation()->getValueNL(),
            'locationEn' => $this->getLocation()->getValueEN(),
            'costs' => $this->getCosts()->getValueNL(),
            'costsEn' => $this->getCosts()->getValueEN(),
            'description' => $this->getDescription()->getValueNL(),
            'descriptionEn' => $this->getDescription()->getValueEN(),
            'organ' => $this->getOrgan(),
            'company' => $this->getCompany(),
            'category' => $this->getCategory()->value,
            'requireGEFLITST' => $this->getRequireGEFLITST(),
            'requireZettle' => $this->getRequireZettle(),
            'labels' => $labelsArrays,
            'signupLists' => $signupListsArrays,
        ];
    }

    /**
     * @return ActivityGdprArrayType
     */
    public function toGdprArray(): array
    {
        /** @var ImportedSignupListGdprArrayType[] $signupListsArrays */
        $signupListsArrays = [];
        foreach ($this->getSignupLists() as $signupList) {
            $signupListsArrays[] = $signupList->toGdprArray();
        }

        /** @var ImportedActivityLabelGdprArrayType[] $labelsArrays */
        $labelsArrays = [];
        foreach ($this->getLabels() as $label) {
            $labelsArrays[] = $label->toGdprArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->toGdprArray(),
            'beginTime' => $this->getBeginTime()->format(DateTimeInterface::ATOM),
            'endTime' => $this->getEndTime()->format(DateTimeInterface::ATOM),
            'location' => $this->getLocation()->toGdprArray(),
            'costs' => $this->getCosts()->toGdprArray(),
            'description' => $this->getDescription()->toGdprArray(),
            'organ' => $this->getOrgan()?->getId(),
            'company' => $this->getCompany()?->getId(),
            'category' => $this->getCategory()->value,
            'requireGEFLITST' => $this->getRequireGEFLITST(),
            'requireZettle' => $this->getRequireZettle(),
            'labels' => $labelsArrays,
            'signupLists' => $signupListsArrays,
        ];
    }
}
