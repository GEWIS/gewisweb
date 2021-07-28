<?php

namespace Activity\Model;

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
    ManyToOne,
    OneToMany,
    OneToOne,
    OrderBy,
};
use User\Model\User as UserModel;
use User\Permissions\Resource\{
    CreatorResourceInterface,
    OrganResourceInterface,
};

/**
 * SignupList model.
 */
#[Entity]
class SignupList implements OrganResourceInterface, CreatorResourceInterface
{
    /**
     * ID for the SignupList.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    /**
     * The Activity this SignupList belongs to.
     */
    #[ManyToOne(
        targetEntity: "Activity\Model\Activity",
        cascade: ["persist"],
        inversedBy: "signupLists",
    )]
    #[JoinColumn(
        name: "activity_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Activity $activity;

    /**
     * The name of the SignupList.
     */
    #[OneToOne(
        targetEntity: "Activity\Model\Activity",
        cascade: ["persist"],
        orphanRemoval: true,
    )]
    protected LocalisedText $name;

    /**
     * The date and time the SignupList is open for signups.
     */
    #[Column(type: "datetime")]
    protected DateTime $openDate;

    /**
     * The date and time after which the SignupList is no longer open.
     */
    #[Column(type: "datetime")]
    protected DateTime $closeDate;

    /**
     * Determines if people outside of GEWIS can sign up.
     */
    #[Column(type: "boolean")]
    protected bool $onlyGEWIS;

    /**
     * Determines if the number of signed up members should be displayed
     * when the user is NOT logged in.
     */
    #[Column(type: "boolean")]
    protected bool $displaySubscribedNumber;

    /**
     * All additional fields belonging to the activity.
     */
    #[OneToMany(
        targetEntity: "Activity\Model\SignupField",
        mappedBy: "signupList",
        orphanRemoval: true,
    )]
    protected $fields;

    /**
     * All the people who signed up for this SignupList.
     */
    #[OneToMany(
        targetEntity: "Activity\Model\Signup",
        mappedBy: "signupList",
        orphanRemoval: true,
    )]
    #[OrderBy(value: ["id" => "ASC"])]
    protected Collection $signUps;

    public function __construct() {
        $this->signUps = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getSignUps(): Collection
    {
        return $this->signUps;
    }

    /**
     * @param Collection $signUps
     */
    public function setSignUps(Collection $signUps): void
    {
        $this->signUps = $signUps;
    }

    /**
     * @return Collection
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @param Collection $fields
     */
    public function setFields(Collection $fields): void
    {
        $this->fields = $fields;
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
     * Returns the opening DateTime of this SignupList.
     *
     * @return DateTime
     */
    public function getOpenDate(): DateTime
    {
        return $this->openDate;
    }

    /**
     * Sets the opening DateTime of this SignupList.
     *
     * @param DateTime $openDate
     */
    public function setOpenDate(DateTime $openDate): void
    {
        $this->openDate = $openDate;
    }

    /**
     * Returns the closing DateTime of this SignupList.
     *
     * @return DateTime
     */
    public function getCloseDate(): DateTime
    {
        return $this->closeDate;
    }

    /**
     * Sets the closing DateTime of this SignupList.
     *
     * @param DateTime $closeDate
     */
    public function setCloseDate(DateTime $closeDate): void
    {
        $this->closeDate = $closeDate;
    }

    /**
     * Returns true if this SignupList is only available to members of GEWIS.
     *
     * @return bool
     */
    public function getOnlyGEWIS(): bool
    {
        return $this->onlyGEWIS;
    }

    /**
     * Sets whether or not this SignupList is available to members of GEWIS.
     *
     * @param bool $onlyGEWIS
     */
    public function setOnlyGEWIS(bool $onlyGEWIS): void
    {
        $this->onlyGEWIS = $onlyGEWIS;
    }

    /**
     * Returns true if this SignupList shows the number of members who signed up
     * when the user is not logged in.
     *
     * @return bool
     */
    public function getDisplaySubscribedNumber(): bool
    {
        return $this->displaySubscribedNumber;
    }

    /**
     * Sets whether or not this SignupList should show the number of members who
     * signed up when the user is not logged in.
     *
     * @param bool $displaySubscribedNumber
     */
    public function setDisplaySubscribedNumber(bool $displaySubscribedNumber): void
    {
        $this->displaySubscribedNumber = $displaySubscribedNumber;
    }

    /**
     * Returns the associated Activity.
     *
     * @return Activity
     */
    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * Sets the associated Activity.
     *
     * @param Activity $activity
     */
    public function setActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->getFields() as $field) {
            $fields[] = $field->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'openDate' => $this->getOpenDate(),
            'closeDate' => $this->getCloseDate(),
            'onlyGEWIS' => $this->getOnlyGEWIS(),
            'displaySubscribedNumber' => $this->getDisplaySubscribedNumber(),
            'fields' => $fields,
        ];
    }

    /**
     * Returns the string identifier of the Resource.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'signupList';
    }

    /**
     * Get the organ of this resource.
     *
     * @return OrganModel|null
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getActivity()->getOrgan();
    }

    /**
     * Get the creator of this resource.
     *
     * @return UserModel
     */
    public function getResourceCreator(): UserModel
    {
        return $this->getActivity()->getCreator();
    }
}
