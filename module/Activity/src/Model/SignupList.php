<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\LocalisedText as LocalisedTextModel;
use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use User\Permissions\Resource\CreatorResourceInterface;
use User\Permissions\Resource\OrganResourceInterface;

/**
 * SignupList model.
 *
 * @psalm-import-type SignupFieldArrayType from SignupField as ImportedSignupFieldArrayType
 * @psalm-type SignupListArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 *     openDate: datetime,
 *     closeDate: datetime,
 *     onlyGEWIS: bool,
 *     displaySubscribedNumber: bool,
 *     limitedCapacity: bool,
 *     fields: ImportedSignupFieldArrayType[],
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @psalm-import-type SignupFieldGdprArrayType from SignupField as ImportedSignupFieldGdprArrayType
 * @psalm-type SignupListGdprArrayType = array{
 *     id: int,
 *     name: ImportedLocalisedTextGdprArrayType,
 *     openDate: string,
 *     closeDate: string,
 *     onlyGEWIS: bool,
 *     displaySubscribedNumber: bool,
 *     limitedCapacity: bool,
 *     fields: ImportedSignupFieldGdprArrayType[],
 * }
 */
#[Entity]
class SignupList implements OrganResourceInterface, CreatorResourceInterface
{
    use IdentifiableTrait;

    /**
     * The Activity this SignupList belongs to.
     */
    #[ManyToOne(
        targetEntity: Activity::class,
        cascade: ['persist'],
        inversedBy: 'signupLists',
    )]
    #[JoinColumn(
        name: 'activity_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Activity $activity;

    /**
     * The name of the SignupList.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $name;

    /**
     * The date and time the SignupList is open for signups.
     */
    #[Column(type: 'datetime')]
    protected DateTime $openDate;

    /**
     * The date and time after which the SignupList is no longer open.
     */
    #[Column(type: 'datetime')]
    protected DateTime $closeDate;

    /**
     * Determines if people outside of GEWIS can sign up.
     */
    #[Column(type: 'boolean')]
    protected bool $onlyGEWIS;

    /**
     * Determines if the number of signed up members should be displayed
     * when the user is NOT logged in.
     */
    #[Column(type: 'boolean')]
    protected bool $displaySubscribedNumber;

    /**
     * If the sign-up list has limited capacity, we should show users a warning that this is the case.
     */
    #[Column(type: 'boolean')]
    protected bool $limitedCapacity;

    /**
     * All additional fields belonging to the activity.
     *
     * @var Collection<array-key, SignupField>
     */
    #[OneToMany(
        targetEntity: SignupField::class,
        mappedBy: 'signupList',
        orphanRemoval: true,
    )]
    protected Collection $fields;

    /**
     * All the people who signed up for this SignupList.
     *
     * @var Collection<array-key, Signup>
     */
    #[OneToMany(
        targetEntity: Signup::class,
        mappedBy: 'signupList',
        orphanRemoval: true,
    )]
    #[OrderBy(value: ['id' => 'ASC'])]
    protected Collection $signUps;

    public function __construct()
    {
        $this->signUps = new ArrayCollection();
    }

    /**
     * @return Collection<array-key, Signup>
     */
    public function getSignUps(): Collection
    {
        return $this->signUps;
    }

    /**
     * @param Collection<array-key, Signup> $signUps
     */
    public function setSignUps(Collection $signUps): void
    {
        $this->signUps = $signUps;
    }

    /**
     * @return Collection<array-key, SignupField>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @param Collection<array-key, SignupField> $fields
     */
    public function setFields(Collection $fields): void
    {
        $this->fields = $fields;
    }

    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the opening DateTime of this SignupList.
     */
    public function getOpenDate(): DateTime
    {
        return $this->openDate;
    }

    /**
     * Sets the opening DateTime of this SignupList.
     */
    public function setOpenDate(DateTime $openDate): void
    {
        $this->openDate = $openDate;
    }

    /**
     * Returns the closing DateTime of this SignupList.
     */
    public function getCloseDate(): DateTime
    {
        return $this->closeDate;
    }

    /**
     * Sets the closing DateTime of this SignupList.
     */
    public function setCloseDate(DateTime $closeDate): void
    {
        $this->closeDate = $closeDate;
    }

    /**
     * Whether the sign-up list period is now.
     *
     * NOTE: this does not indicate that one is able to sign up, that depends on other factors such as approval status
     * of the actual activity.
     */
    public function isOpen(): bool
    {
        $now = new DateTime('now');

        return $now >= $this->getOpenDate() && $now < $this->getCloseDate();
    }

    /**
     * Returns true if this SignupList is only available to members of GEWIS.
     */
    public function getOnlyGEWIS(): bool
    {
        return $this->onlyGEWIS;
    }

    /**
     * Sets whether or not this SignupList is available to members of GEWIS.
     */
    public function setOnlyGEWIS(bool $onlyGEWIS): void
    {
        $this->onlyGEWIS = $onlyGEWIS;
    }

    /**
     * Returns true if this SignupList shows the number of members who signed up
     * when the user is not logged in.
     */
    public function getDisplaySubscribedNumber(): bool
    {
        return $this->displaySubscribedNumber;
    }

    /**
     * Sets whether or not this SignupList should show the number of members who
     * signed up when the user is not logged in.
     */
    public function setDisplaySubscribedNumber(bool $displaySubscribedNumber): void
    {
        $this->displaySubscribedNumber = $displaySubscribedNumber;
    }

    /**
     * Returns true if this SignupList has a limited capacity.
     */
    public function getLimitedCapacity(): bool
    {
        return $this->limitedCapacity;
    }

    /**
     * Sets whether or not this SignupList has limited capacity.
     */
    public function setLimitedCapacity(bool $limitedCapacity): void
    {
        $this->limitedCapacity = $limitedCapacity;
    }

    /**
     * Returns the associated Activity.
     */
    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * Sets the associated Activity.
     */
    public function setActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return SignupListArrayType
     */
    public function toArray(): array
    {
        $fieldsArrays = [];
        foreach ($this->getFields() as $field) {
            $fieldsArrays[] = $field->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'openDate' => $this->getOpenDate(),
            'closeDate' => $this->getCloseDate(),
            'onlyGEWIS' => $this->getOnlyGEWIS(),
            'displaySubscribedNumber' => $this->getDisplaySubscribedNumber(),
            'limitedCapacity' => $this->getLimitedCapacity(),
            'fields' => $fieldsArrays,
        ];
    }

    /**
     * @return SignupListGdprArrayType
     */
    public function toGdprArray(): array
    {
        /** @var ImportedSignupFieldGdprArrayType[] $fieldsArrays */
        $fieldsArrays = [];
        foreach ($this->getFields() as $field) {
            $fieldsArrays[] = $field->toGdprArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->toGdprArray(),
            'openDate' => $this->getOpenDate()->format(DateTimeInterface::ATOM),
            'closeDate' => $this->getCloseDate()->format(DateTimeInterface::ATOM),
            'onlyGEWIS' => $this->getOnlyGEWIS(),
            'displaySubscribedNumber' => $this->getDisplaySubscribedNumber(),
            'limitedCapacity' => $this->getLimitedCapacity(),
            'fields' => $fieldsArrays,
        ];
    }

    /**
     * Returns the string identifier of the Resource.
     */
    public function getResourceId(): string
    {
        return 'signupList';
    }

    /**
     * Get the organ of this resource.
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getActivity()->getOrgan();
    }

    /**
     * Get the creator of this resource.
     */
    public function getResourceCreator(): MemberModel
    {
        return $this->getActivity()->getCreator();
    }
}
