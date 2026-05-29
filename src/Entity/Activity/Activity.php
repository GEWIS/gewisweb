<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Application\LocalisedText as LocalisedTextModel;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Career\Company as CompanyModel;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ as OrganModel;
use App\Repository\Activity\ActivityRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * Activity model.
 *
 * @psalm-import-type ActivityLabelArrayType from ActivityLabel as ImportedActivityLabelArrayType
 * @psalm-import-type SignupListArrayType from SignupList as ImportedSignupListArrayType
 * @psalm-type ActivityArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 *     beginTime: datetime,
 *     endTime: datetime,
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
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
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
class Activity
{
    use IdentifiableTrait;

    /**
     * Status codes for the activity.
     */
    public const int STATUS_TO_APPROVE = 1; // Activity needs to be approved
    public const int STATUS_APPROVED = 2;  // The activity is approved
    public const int STATUS_DISAPPROVED = 3; // The board disapproved the activity
    public const int STATUS_UPDATE = 4; // This activity is an update for some activity

    /**
     * Name for the activity.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
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
    private ActivityLocalisedText $name;

    /**
     * The date and time the activity starts.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $beginTime;

    /**
     * The date and time the activity ends.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $endTime;

    /**
     * The location the activity is held at.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
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
    private ActivityLocalisedText $location;

    /**
     * How much does it cost.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'costs_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $costs;

    /**
     * Who (dis)approved this activity?
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    private ?MemberModel $approver = null;

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
     * What is the approval status      .
     */
    #[Column(type: Types::INTEGER)]
    private int $status;

    /**
     * The update proposal associated with this activity.
     *
     * @var Collection<array-key, ActivityUpdateProposal>
     */
    #[OneToMany(
        targetEntity: ActivityUpdateProposal::class,
        mappedBy: 'old',
    )]
    private Collection $updateProposal;

    /**
     * Activity description.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
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
    private ActivityLocalisedText $description;

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
     * @var Collection<array-key, SignupList>
     */
    #[OneToMany(
        targetEntity: SignupList::class,
        mappedBy: 'activity',
        cascade: ['remove'],
        orphanRemoval: true,
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

    /**
     * The (single, mandatory) category of this activity.
     */
    #[Column(
        type: Types::STRING,
        enumType: ActivityCategories::class,
    )]
    private ActivityCategories $category;

    /**
     * Whether this activity needs a GEFLITST photographer.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $requireGEFLITST = false;

    /**
     * Whether this activity needs a Zettle.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $requireZettle = false;

    public function __construct()
    {
        $this->updateProposal = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->signupLists = new ArrayCollection();
    }

    public function getApprover(): ?MemberModel
    {
        return $this->approver;
    }

    public function setApprover(?MemberModel $approver): void
    {
        $this->approver = $approver;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection<array-key, ActivityUpdateProposal>
     */
    public function getUpdateProposal(): Collection
    {
        return $this->updateProposal;
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

    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    public function getBeginTime(): DateTime
    {
        return $this->beginTime;
    }

    public function setBeginTime(DateTime $beginTime): void
    {
        $this->beginTime = $beginTime;
    }

    public function getEndTime(): DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(DateTime $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getLocation(): ActivityLocalisedText
    {
        return $this->location;
    }

    public function setLocation(ActivityLocalisedText $location): void
    {
        $this->location = $location;
    }

    public function getCosts(): ActivityLocalisedText
    {
        return $this->costs;
    }

    public function setCosts(ActivityLocalisedText $costs): void
    {
        $this->costs = $costs;
    }

    public function getDescription(): ActivityLocalisedText
    {
        return $this->description;
    }

    public function setDescription(ActivityLocalisedText $description): void
    {
        $this->description = $description;
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

    public function getCategory(): ActivityCategories
    {
        return $this->category;
    }

    public function setCategory(ActivityCategories $category): void
    {
        $this->category = $category;
    }

    public function getRequireGEFLITST(): bool
    {
        return $this->requireGEFLITST;
    }

    public function setRequireGEFLITST(bool $requireGEFLITST): void
    {
        $this->requireGEFLITST = $requireGEFLITST;
    }

    public function getRequireZettle(): bool
    {
        return $this->requireZettle;
    }

    public function setRequireZettle(bool $requireZettle): void
    {
        $this->requireZettle = $requireZettle;
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

    /**
     * Returns the string identifier of the Resource.
     */
    public function getResourceId(): string
    {
        return 'activity';
    }

    /**
     * Get the organ of this resource.
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getOrgan();
    }

    /**
     * Get the creator of this resource.
     */
    public function getResourceCreator(): MemberModel
    {
        return $this->getCreator();
    }
}
