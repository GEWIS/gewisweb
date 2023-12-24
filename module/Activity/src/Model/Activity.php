<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\LocalisedText as LocalisedTextModel;
use Application\Model\Traits\IdentifiableTrait;
use Company\Model\Company as CompanyModel;
use DateTime;
use DateTimeInterface;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use User\Permissions\Resource\CreatorResourceInterface;
use User\Permissions\Resource\OrganResourceInterface;

/**
 * Activity model.
 *
 * @psalm-import-type ActivityCategoryArrayType from ActivityCategory as ImportedActivityCategoryArrayType
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
 *     isMyFuture: bool,
 *     requireGEFLITST: bool,
 *     categories: ImportedActivityCategoryArrayType[],
 *     signupLists: ImportedSignupListArrayType[],
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @psalm-import-type ActivityCategoryGdprArrayType from ActivityCategory as ImportedActivityCategoryGdprArrayType
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
 *     isMyFuture: bool,
 *     requireGEFLITST: bool,
 *     categories: ImportedActivityCategoryGdprArrayType[],
 *     signupLists: ImportedSignupListGdprArrayType[],
 * }
 */
#[Entity]
class Activity implements OrganResourceInterface, CreatorResourceInterface
{
    use IdentifiableTrait;

    /**
     * Status codes for the activity.
     */
    public const STATUS_TO_APPROVE = 1; // Activity needs to be approved
    public const STATUS_APPROVED = 2;  // The activity is approved
    public const STATUS_DISAPPROVED = 3; // The board disapproved the activity
    public const STATUS_UPDATE = 4; // This activity is an update for some activity

    /**
     * Name for the activity.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $name;

    /**
     * The date and time the activity starts.
     */
    #[Column(type: 'datetime')]
    protected DateTime $beginTime;

    /**
     * The date and time the activity ends.
     */
    #[Column(type: 'datetime')]
    protected DateTime $endTime;

    /**
     * The location the activity is held at.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'location_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $location;

    /**
     * How much does it cost.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'costs_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $costs;

    /**
     * Who (dis)approved this activity?
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    protected ?MemberModel $approver = null;

    /**
     * Who created this activity.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $creator;

    /**
     * What is the approval status      .
     */
    #[Column(type: 'integer')]
    protected int $status;

    /**
     * The update proposal associated with this activity.
     *
     * @var Collection<array-key, ActivityUpdateProposal>
     */
    #[OneToMany(
        targetEntity: ActivityUpdateProposal::class,
        mappedBy: 'old',
    )]
    protected Collection $updateProposal;

    /**
     * Activity description.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'description_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $description;

    /**
     * All additional Categories belonging to this activity.
     *
     * @var Collection<array-key, ActivityCategory>
     */
    #[ManyToMany(
        targetEntity: ActivityCategory::class,
        inversedBy: 'activities',
        cascade: ['persist'],
    )]
    #[JoinTable(name: 'ActivityCategoryAssignment')]
    protected Collection $categories;

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
    protected Collection $signupLists;

    /**
     * Which organ organises this activity.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    protected ?OrganModel $organ = null;

    /**
     * Which company organises this activity.
     */
    #[ManyToOne(targetEntity: CompanyModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    protected ?CompanyModel $company = null;

    /**
     * Is this a My Future related activity.
     */
    #[Column(type: 'boolean')]
    protected bool $isMyFuture;

    /**
     * Whether this activity needs a GEFLITST photographer.
     */
    #[Column(type: 'boolean')]
    protected bool $requireGEFLITST;

    public function __construct()
    {
        $this->updateProposal = new ArrayCollection();
        $this->categories = new ArrayCollection();
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
     * @param ActivityCategory[] $categories
     */
    public function addCategories(array $categories): void
    {
        foreach ($categories as $category) {
            $this->addCategory($category);
        }
    }

    public function addCategory(ActivityCategory $category): void
    {
        if ($this->categories->contains($category)) {
            return;
        }

        $this->categories->add($category);
        $category->addActivity($this);
    }

    /**
     * @param ActivityCategory[] $categories
     */
    public function removeCategories(array $categories): void
    {
        foreach ($categories as $category) {
            $this->removeCategory($category);
        }
    }

    public function removeCategory(ActivityCategory $category): void
    {
        if (!$this->categories->contains($category)) {
            return;
        }

        $this->categories->removeElement($category);
        $category->removeActivity($this);
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

    public function getIsMyFuture(): bool
    {
        return $this->isMyFuture;
    }

    public function setIsMyFuture(bool $isMyFuture): void
    {
        $this->isMyFuture = $isMyFuture;
    }

    public function getRequireGEFLITST(): bool
    {
        return $this->requireGEFLITST;
    }

    public function setRequireGEFLITST(bool $requireGEFLITST): void
    {
        $this->requireGEFLITST = $requireGEFLITST;
    }

    /**
     * @return Collection<array-key, ActivityCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
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

        $categoriesArrays = [];
        foreach ($this->getCategories() as $category) {
            $categoriesArrays[] = $category->toArray();
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
            'isMyFuture' => $this->getIsMyFuture(),
            'requireGEFLITST' => $this->getRequireGEFLITST(),
            'categories' => $categoriesArrays,
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

        /** @var ImportedActivityCategoryGdprArrayType[] $categoriesArrays */
        $categoriesArrays = [];
        foreach ($this->getCategories() as $category) {
            $categoriesArrays[] = $category->toGdprArray();
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
            'isMyFuture' => $this->getIsMyFuture(),
            'requireGEFLITST' => $this->getRequireGEFLITST(),
            'categories' => $categoriesArrays,
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
