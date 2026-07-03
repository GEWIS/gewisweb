<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\DrawCutoffRule;
use App\Entity\Application\LocalisedText as LocalisedTextModel;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ as OrganModel;
use App\Repository\Activity\SignupListRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

use function sprintf;

/**
 * SignupList model.
 *
 * @psalm-import-type SignupFieldArrayType from SignupField as ImportedSignupFieldArrayType
 * @psalm-type SignupListArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 *     openDate: DateTime,
 *     closeDate: DateTime,
 *     onlyGEWIS: bool,
 *     displaySubscribedNumber: bool,
 *     limitedCapacity: bool,
 *     capacity: ?int,
 *     allocationMethod: string,
 *     drawCutoffRule: ?string,
 *     drawCutoffAt: ?DateTime,
 *     drawAfterDurationHours: ?int,
 *     externalPolicyUrl: ?string,
 *     externalForceOrdering: bool,
 *     externalPaymentByExternal: bool,
 *     customMethodDescription: ?string,
 *     fields: ImportedSignupFieldArrayType[],
 *     presenceTaken: bool,
 *     promoted: bool,
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
 *     capacity: ?int,
 *     allocationMethod: string,
 *     drawCutoffRule: ?string,
 *     drawCutoffAt: ?string,
 *     drawAfterDurationHours: ?int,
 *     externalPolicyUrl: ?string,
 *     externalForceOrdering: bool,
 *     externalPaymentByExternal: bool,
 *     customMethodDescription: ?string,
 *     fields: ImportedSignupFieldGdprArrayType[],
 *     presenceTaken: bool,
 *     promoted: bool
 * }
 */
#[Entity(repositoryClass: SignupListRepository::class)]
#[UniqueConstraint(
    name: 'signup_list_revision_lineage_uniq',
    columns: [
        'activity_revision_id',
        'lineageId',
    ],
)]
class SignupList
{
    use IdentifiableTrait;

    /**
     * The revision this SignupList belongs to. Each revision owns its own (cloned) lists, so list edits are staged
     * with the rest of the revision and only become public when the revision is approved.
     */
    #[ManyToOne(
        targetEntity: ActivityRevision::class,
        cascade: ['persist'],
        inversedBy: 'signupLists',
    )]
    #[JoinColumn(
        name: 'activity_revision_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityRevision $revision;

    /**
     * Stable identity shared by every clone of this logical list across revisions. On approval, sign-ups are migrated
     * from the outgoing live revision's list to the newly-approved revision's clone with the same lineage id.
     */
    #[Column(type: UuidType::NAME)]
    private Uuid $lineageId;

    /**
     * The name of the SignupList.
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
     * The date and time the SignupList is open for signups.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $openDate;

    /**
     * The date and time after which the SignupList is no longer open.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $closeDate;

    /**
     * Determines if people outside of GEWIS can sign up.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $onlyGEWIS = false;

    /**
     * Determines if the number of signed up members should be displayed
     * when the user is NOT logged in.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $displaySubscribedNumber = false;

    /**
     * If the sign-up list has limited capacity, we should show users a warning that this is the case.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $limitedCapacity = false;

    /**
     * The maximum number of admitted sign-ups when {@see self::$limitedCapacity} is set; null when unlimited.
     * Subscribees to a limited list must first be drawn (admitted) up to this number before attendance can be taken.
     */
    #[Column(
        type: Types::INTEGER,
        nullable: true,
    )]
    private ?int $capacity = null;

    /**
     * When the admission draw was performed and locked, or null if it has not been drawn yet. A non-null value marks
     * the draw as immutable: the bulk draw can no longer be re-run, only individual admissions adjusted. Mirrors the
     * reviewer/reviewedAt audit on {@see \App\Entity\Application\AbstractRevision}.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $drawnAt = null;

    /**
     * The board member who performed (and locked) the draw; null while not drawn, and also for a draw performed
     * automatically at its deadline (so null with a non-null {@see self::$drawnAt} means an automated draw).
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?MemberModel $drawnBy = null;

    /**
     * How the limited places are allocated among subscribers (only meaningful when {@see self::$limitedCapacity}).
     */
    #[Column(
        type: Types::STRING,
        enumType: AllocationMethod::class,
    )]
    private AllocationMethod $allocationMethod = AllocationMethod::FirstComeFirstServed;

    /**
     * For an {@see AllocationMethod::ConditionalDraw}: when the draw should be performed.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
        enumType: DrawCutoffRule::class,
    )]
    private ?DrawCutoffRule $drawCutoffRule = null;

    /**
     * For {@see DrawCutoffRule::IfFullBefore}: the moment the list must be full by.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $drawCutoffAt = null;

    /**
     * For {@see DrawCutoffRule::AfterDurationOpen}: how many hours after opening the draw happens.
     */
    #[Column(
        type: Types::INTEGER,
        nullable: true,
    )]
    private ?int $drawAfterDurationHours = null;

    /**
     * For an {@see AllocationMethod::ExternalParty}: a URL describing the external party's allocation policy.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $externalPolicyUrl = null;

    /**
     * For an {@see AllocationMethod::ExternalParty}: whether the external party dictates the ordering of admissions.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $externalForceOrdering = false;

    /**
     * For an {@see AllocationMethod::ExternalParty}: whether payment is collected by the external party.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $externalPaymentByExternal = false;

    /**
     * For a {@see AllocationMethod::Custom}: a free-form description of how places are allocated.
     */
    #[Column(
        type: Types::TEXT,
        nullable: true,
    )]
    private ?string $customMethodDescription = null;

    /**
     * All additional fields belonging to the activity.
     *
     * @var Collection<array-key, SignupField>
     */
    #[OneToMany(
        mappedBy: 'signupList',
        targetEntity: SignupField::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[OrderBy(['id' => 'ASC'])]
    private Collection $fields;

    /**
     * All the people who signed up for this SignupList.
     *
     * @var Collection<array-key, Signup>
     */
    #[OneToMany(
        mappedBy: 'signupList',
        targetEntity: Signup::class,
        orphanRemoval: true,
    )]
    #[OrderBy(value: ['id' => 'ASC'])]
    private Collection $signUps;

    /**
     * Determines if presence was taken for this SignupList
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $presenceTaken = false;

    /**
     * Determines if the signup list should appear before other signup lists on the same activity.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $promoted = false;

    public function __construct()
    {
        $this->signUps = new ArrayCollection();
        $this->fields = new ArrayCollection();
        // Initialise the required scalars/relations so a freshly-formed (not-yet-hydrated) list is form-ready;
        // Doctrine bypasses the constructor when hydrating, so existing rows keep their persisted values.
        $this->name = new ActivityLocalisedText();
        $this->openDate = new DateTime();
        $this->closeDate = new DateTime();
        // A brand-new list starts its own lineage; the cloner copies this id onto each clone.
        $this->lineageId = Uuid::v4();
    }

    public function addField(SignupField $field): void
    {
        if ($this->fields->contains($field)) {
            return;
        }

        $this->fields->add($field);
        $field->setSignupList($this);
    }

    public function removeField(SignupField $field): void
    {
        $this->fields->removeElement($field);
    }

    /**
     * Whether anyone has signed up for this list. Once true, the list's structure is frozen (only safe metadata may
     * change) so existing sign-ups are never invalidated.
     */
    public function hasSignUps(): bool
    {
        return !$this->signUps->isEmpty();
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
     * Whether the sign-up period has ended. Not the inverse of {@see self::isOpen()}: a list before its open date is
     * neither open nor closed. Drawing/admission only makes sense once subscriptions can no longer change, i.e. once
     * this is true.
     */
    public function isClosed(): bool
    {
        return new DateTime('now') >= $this->getCloseDate();
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
     * The maximum number of admitted sign-ups (only meaningful when limited capacity); null when unlimited.
     */
    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): void
    {
        $this->capacity = $capacity;
    }

    /**
     * When the draw was performed and locked; null while not yet drawn. A non-null value means the draw is locked.
     */
    public function getDrawnAt(): ?DateTime
    {
        return $this->drawnAt;
    }

    public function setDrawnAt(?DateTime $drawnAt): void
    {
        $this->drawnAt = $drawnAt;
    }

    /**
     * Whether the admission draw has been performed and locked.
     */
    public function isDrawLocked(): bool
    {
        return null !== $this->drawnAt;
    }

    /**
     * The moment the automated draw is due for this list, or `null` when it is never drawn automatically.
     *
     * Lists with unlimited capacity, manual allocation methods, or a conditional draw without a cutoff rule (only for
     * legacy sign-up lists) have no draw moment. First-come-first-served draws at close; a conditional draw at
     * the moment its {@see DrawCutoffRule} describes.
     */
    public function getAutoDrawAt(): ?DateTime
    {
        if (!$this->limitedCapacity) {
            return null;
        }

        if (AllocationMethod::FirstComeFirstServed === $this->allocationMethod) {
            return $this->closeDate;
        }

        if (AllocationMethod::ConditionalDraw !== $this->allocationMethod) {
            return null;
        }

        return match ($this->drawCutoffRule) {
            DrawCutoffRule::OnClose => $this->closeDate,
            DrawCutoffRule::IfFullBefore => $this->drawCutoffAt,
            DrawCutoffRule::AfterDurationOpen => null === $this->drawAfterDurationHours
                ? null
                : (clone $this->openDate)->modify(sprintf(
                    '+%d hours',
                    $this->drawAfterDurationHours,
                )),
            null => null,
        };
    }

    /**
     * Whether the automated draw moment has passed (regardless of whether the draw has actually run yet).
     */
    public function isAutoDrawDue(): bool
    {
        $dueAt = $this->getAutoDrawAt();

        return null !== $dueAt
            && new DateTime('now') >= $dueAt;
    }

    public function getDrawnBy(): ?MemberModel
    {
        return $this->drawnBy;
    }

    public function setDrawnBy(?MemberModel $drawnBy): void
    {
        $this->drawnBy = $drawnBy;
    }

    public function getAllocationMethod(): AllocationMethod
    {
        return $this->allocationMethod;
    }

    public function setAllocationMethod(AllocationMethod $allocationMethod): void
    {
        $this->allocationMethod = $allocationMethod;
    }

    public function getDrawCutoffRule(): ?DrawCutoffRule
    {
        return $this->drawCutoffRule;
    }

    public function setDrawCutoffRule(?DrawCutoffRule $drawCutoffRule): void
    {
        $this->drawCutoffRule = $drawCutoffRule;
    }

    public function getDrawCutoffAt(): ?DateTime
    {
        return $this->drawCutoffAt;
    }

    public function setDrawCutoffAt(?DateTime $drawCutoffAt): void
    {
        $this->drawCutoffAt = $drawCutoffAt;
    }

    public function getDrawAfterDurationHours(): ?int
    {
        return $this->drawAfterDurationHours;
    }

    public function setDrawAfterDurationHours(?int $drawAfterDurationHours): void
    {
        $this->drawAfterDurationHours = $drawAfterDurationHours;
    }

    public function getExternalPolicyUrl(): ?string
    {
        return $this->externalPolicyUrl;
    }

    public function setExternalPolicyUrl(?string $externalPolicyUrl): void
    {
        $this->externalPolicyUrl = $externalPolicyUrl;
    }

    public function getExternalForceOrdering(): bool
    {
        return $this->externalForceOrdering;
    }

    public function setExternalForceOrdering(bool $externalForceOrdering): void
    {
        $this->externalForceOrdering = $externalForceOrdering;
    }

    public function getExternalPaymentByExternal(): bool
    {
        return $this->externalPaymentByExternal;
    }

    public function setExternalPaymentByExternal(bool $externalPaymentByExternal): void
    {
        $this->externalPaymentByExternal = $externalPaymentByExternal;
    }

    public function getCustomMethodDescription(): ?string
    {
        return $this->customMethodDescription;
    }

    public function setCustomMethodDescription(?string $customMethodDescription): void
    {
        $this->customMethodDescription = $customMethodDescription;
    }

    /**
     * Whether this list has been attached to a revision yet. A brand-new list added through the form has none until
     * it is bound; a cloned draft list already does (so its date/freeze rules look through its lineage).
     */
    public function hasRevision(): bool
    {
        return isset($this->revision);
    }

    /**
     * Returns the owning revision.
     */
    public function getRevision(): ActivityRevision
    {
        return $this->revision;
    }

    /**
     * Sets the owning revision.
     */
    public function setRevision(ActivityRevision $revision): void
    {
        $this->revision = $revision;
    }

    /**
     * Returns the activity this list ultimately belongs to (via its owning revision). Kept so resource/GDPR call
     * sites that reach for the activity keep working unchanged.
     */
    public function getActivity(): Activity
    {
        return $this->revision->getActivity();
    }

    public function getLineageId(): Uuid
    {
        return $this->lineageId;
    }

    public function setLineageId(Uuid $lineageId): void
    {
        $this->lineageId = $lineageId;
    }

    /**
     * Gets presenceTaken for this SignupList
     */
    public function isPresenceTaken(): bool
    {
        return $this->presenceTaken;
    }

    /**
     * Sets presenceTaken for this SignupList
     */
    public function setPresenceTaken(bool $presenceTaken): void
    {
        $this->presenceTaken = $presenceTaken;
    }

    /**
     * Get whether signup list is promoted.
     */
    public function isPromoted(): bool
    {
        return $this->promoted;
    }

    /**
     * Set promoted state of signup list.
     */
    public function setPromoted(bool $promoted): void
    {
        $this->promoted = $promoted;
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
            'capacity' => $this->getCapacity(),
            'allocationMethod' => $this->getAllocationMethod()->value,
            'drawCutoffRule' => $this->getDrawCutoffRule()?->value,
            'drawCutoffAt' => $this->getDrawCutoffAt(),
            'drawAfterDurationHours' => $this->getDrawAfterDurationHours(),
            'externalPolicyUrl' => $this->getExternalPolicyUrl(),
            'externalForceOrdering' => $this->getExternalForceOrdering(),
            'externalPaymentByExternal' => $this->getExternalPaymentByExternal(),
            'customMethodDescription' => $this->getCustomMethodDescription(),
            'presenceTaken' => $this->isPresenceTaken(),
            'promoted' => $this->isPromoted(),
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
            'capacity' => $this->getCapacity(),
            'allocationMethod' => $this->getAllocationMethod()->value,
            'drawCutoffRule' => $this->getDrawCutoffRule()?->value,
            'drawCutoffAt' => $this->getDrawCutoffAt()?->format(DateTimeInterface::ATOM),
            'drawAfterDurationHours' => $this->getDrawAfterDurationHours(),
            'externalPolicyUrl' => $this->getExternalPolicyUrl(),
            'externalForceOrdering' => $this->getExternalForceOrdering(),
            'externalPaymentByExternal' => $this->getExternalPaymentByExternal(),
            'customMethodDescription' => $this->getCustomMethodDescription(),
            'presenceTaken' => $this->isPresenceTaken(),
            'promoted' => $this->isPromoted(),
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
     * Get the organ of this resource. Mirrors the activity's edit-rights organ (anchored to the live revision), not
     * this list's own revision's organ, so a draft cannot grant itself edit rights by changing the organiser.
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getActivity()->getResourceOrgan();
    }

    /**
     * Get the creator of this resource.
     */
    public function getResourceCreator(): MemberModel
    {
        return $this->getActivity()->getCreator();
    }
}
