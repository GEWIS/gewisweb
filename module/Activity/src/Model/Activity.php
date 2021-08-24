<?php

namespace Activity\Model;

use Company\Model\Company as CompanyModel;
use DateTime;
use Decision\Model\Organ as OrganModel;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    JoinTable,
    ManyToMany,
    ManyToOne,
    OneToMany,
    OneToOne,
};
use User\Model\User as UserModel;
use User\Permissions\Resource\{
    CreatorResourceInterface,
    OrganResourceInterface,
};

/**
 * Activity model.
 */
#[Entity]
class Activity implements OrganResourceInterface, CreatorResourceInterface
{
    /**
     * Status codes for the activity.
     */
    public const STATUS_TO_APPROVE = 1; // Activity needs to be approved
    public const STATUS_APPROVED = 2;  // The activity is approved
    public const STATUS_DISAPPROVED = 3; // The board disapproved the activity
    public const STATUS_UPDATE = 4; // This activity is an update for some activity

    /**
     * ID for the activity.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected ?int $id = null;

    /**
     * Name for the activity.
     */
    #[OneToOne(
        targetEntity: LocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected LocalisedText $name;

    /**
     * The date and time the activity starts.
     */
    #[Column(type: "datetime")]
    protected DateTime $beginTime;

    /**
     * The date and time the activity ends.
     */
    #[Column(type: "datetime")]
    protected DateTime $endTime;

    /**
     * The location the activity is held at.
     */
    #[OneToOne(
        targetEntity: LocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected LocalisedText $location;

    /**
     * How much does it cost.
     */
    #[OneToOne(
        targetEntity: LocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected LocalisedText $costs;

    /**
     * Who (dis)approved this activity?
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?UserModel $approver = null;

    /**
     * Who created this activity.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected UserModel $creator;

    /**
     * What is the approval status      .
     */
    #[Column(type: "integer")]
    protected int $status;

    /**
     * The update proposal associated with this activity.
     */
    #[OneToMany(
        targetEntity: ActivityUpdateProposal::class,
        mappedBy: "old",
    )]
    protected Collection $updateProposal;

    /**
     * Activity description.
     */
    #[OneToOne(
        targetEntity: LocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected LocalisedText $description;

    /**
     * All additional Categories belonging to this activity.
     */
    #[ManyToMany(
        targetEntity: ActivityCategory::class,
        inversedBy: "activities",
        cascade: ["persist"],
    )]
    #[JoinTable(name: "ActivityCategoryAssignment")]
    protected Collection $categories;

    /**
     * All additional SignupLists belonging to this activity.
     */
    #[OneToMany(
        targetEntity: SignupList::class,
        mappedBy: "activity",
        cascade: ["remove"],
    )]
    protected Collection $signupLists;

    /**
     * Which organ organises this activity.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: true,
    )]
    protected ?OrganModel $organ = null;

    /**
     * Which company organises this activity.
     */
    #[ManyToOne(targetEntity: CompanyModel::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: true,
    )]
    protected ?CompanyModel $company = null;

    /**
     * Is this a My Future related activity.
     */
    #[Column(type: "boolean")]
    protected bool $isMyFuture;

    /**
     * Whether this activity needs a GEFLITST photographer.
     */
    #[Column(type: "boolean")]
    protected bool $requireGEFLITST;

    public function __construct()
    {
        $this->updateProposal = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->signupLists = new ArrayCollection();
    }

    /**
     * @return UserModel|null
     */
    public function getApprover(): ?UserModel
    {
        return $this->approver;
    }

    /**
     * @param UserModel|null $approver
     */
    public function setApprover(?UserModel $approver): void
    {
        $this->approver = $approver;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection
     */
    public function getUpdateProposal(): Collection
    {
        return $this->updateProposal;
    }

    /**
     * @param array $categories
     */
    public function addCategories(array $categories): void
    {
        foreach ($categories as $category) {
            $this->addCategory($category);
        }
    }

    /**
     * @param ActivityCategory $category
     */
    public function addCategory(ActivityCategory $category): void
    {
        if ($this->categories->contains($category)) {
            return;
        }

        $this->categories->add($category);
        $category->addActivity($this);
    }

    /**
     * @param array $categories
     */
    public function removeCategories(array $categories): void
    {
        foreach ($categories as $category) {
            $this->removeCategory($category);
        }
    }

    /**
     * @param ActivityCategory $category
     */
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
     * @param array $signupLists
     */
    public function addSignupLists(array $signupLists): void
    {
        foreach ($signupLists as $signupList) {
            $this->addSignupList($signupList);
        }
    }

    /**
     * @param SignupList $signupList
     */
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
     * @param array $signupLists
     */
    public function removeSignupLists(array $signupLists): void
    {
        foreach ($signupLists as $signupList) {
            $this->removeSignupList($signupList);
        }
    }

    /**
     * @param SignupList $signupList
     */
    public function removeSignupList(SignupList $signupList): void
    {
        if (!$this->signupLists->contains($signupList)) {
            return;
        }

        $this->signupLists->removeElement($signupList);
        $signupList->setActivity(null);
    }

    /**
     * Returns an Collection of SignupLists associated with this activity.
     *
     * @return Collection
     */
    public function getSignupLists(): Collection
    {
        return $this->signupLists;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return LocalisedText
     */
    public function getName(): LocalisedText
    {
        return $this->name;
    }

    /**
     * @param LocalisedText $name
     */
    public function setName(LocalisedText $name): void
    {
        $this->name = $name->copy();
    }

    /**
     * @return DateTime
     */
    public function getBeginTime(): DateTime
    {
        return $this->beginTime;
    }

    /**
     * @param DateTime $beginTime
     */
    public function setBeginTime(DateTime $beginTime): void
    {
        $this->beginTime = $beginTime;
    }

    /**
     * @return DateTime
     */
    public function getEndTime(): DateTime
    {
        return $this->endTime;
    }

    /**
     * @param DateTime $endTime
     */
    public function setEndTime(DateTime $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @return LocalisedText
     */
    public function getLocation(): LocalisedText
    {
        return $this->location;
    }

    /**
     * @param LocalisedText $location
     */
    public function setLocation(LocalisedText $location): void
    {
        $this->location = $location->copy();
    }

    /**
     * @return LocalisedText
     */
    public function getCosts(): LocalisedText
    {
        return $this->costs;
    }

    /**
     * @param LocalisedText $costs
     */
    public function setCosts(LocalisedText $costs): void
    {
        $this->costs = $costs->copy();
    }

    /**
     * @return LocalisedText
     */
    public function getDescription(): LocalisedText
    {
        return $this->description;
    }

    /**
     * @param LocalisedText $description
     */
    public function setDescription(LocalisedText $description): void
    {
        $this->description = $description->copy();
    }

    /**
     * @return OrganModel|null
     */
    public function getOrgan(): ?OrganModel
    {
        return $this->organ;
    }

    /**
     * @param OrganModel|null $organ
     */
    public function setOrgan(?OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * @return CompanyModel|null
     */
    public function getCompany(): ?CompanyModel
    {
        return $this->company;
    }

    /**
     * @param CompanyModel|null $company
     */
    public function setCompany(?CompanyModel $company): void
    {
        $this->company = $company;
    }

    /**
     * @return bool
     */
    public function getIsMyFuture(): bool
    {
        return $this->isMyFuture;
    }

    /**
     * @param bool $isMyFuture
     */
    public function setIsMyFuture(bool $isMyFuture): void
    {
        $this->isMyFuture = $isMyFuture;
    }

    /**
     * @return bool
     */
    public function getRequireGEFLITST(): bool
    {
        return $this->requireGEFLITST;
    }

    /**
     * @param bool $requireGEFLITST
     */
    public function setRequireGEFLITST(bool $requireGEFLITST): void
    {
        $this->requireGEFLITST = $requireGEFLITST;
    }

    /**
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @return UserModel
     */
    public function getCreator(): UserModel
    {
        return $this->creator;
    }

    public function setCreator(UserModel $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray(): array
    {
        $signupLists = [];
        foreach ($this->getSignupLists() as $signupList) {
            $signupLists[] = $signupList->toArray();
        }

        $categories = [];
        foreach ($this->getCategories() as $category) {
            $categories[] = $category->toArray();
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
            'categories' => $categories,
            'signupLists' => $signupLists,
        ];
    }

    /**
     * Returns the string identifier of the Resource.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'activity';
    }

    /**
     * Get the organ of this resource.
     *
     * @return OrganModel|null
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getOrgan();
    }

    /**
     * Get the creator of this resource.
     *
     * @return UserModel
     */
    public function getResourceCreator(): UserModel
    {
        return $this->getCreator();
    }
}
