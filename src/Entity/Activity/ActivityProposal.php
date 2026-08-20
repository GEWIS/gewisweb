<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\BudgetClearance;
use App\Entity\Activity\Enums\DateOptionStatus;
use App\Entity\Activity\Enums\ProposalStatus;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Repository\Activity\ActivityProposalRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\PrePersist;
use LogicException;

use function count;
use function sprintf;

/**
 * An activity a body would like to host, put forward during an option period with up to three dates it could be on.
 *
 * The board picks one of the dates, which reserves it and starts the real activity off as a draft
 * ({@see self::$activity}). From there it is the ordinary activity workflow; this entity only holds the date until
 * then, and records whether the financial side has been settled in time to keep it.
 */
#[Entity(repositoryClass: ActivityProposalRepository::class)]
#[HasLifecycleCallbacks]
#[Index(
    fields: [
        'period',
        'organ',
    ],
    name: 'activity_proposal_period_organ',
)]
class ActivityProposal
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * How many dates a body may put forward for one activity. A house rule as old as the paper calendar.
     */
    public const int MAX_DATE_OPTIONS = 3;

    /**
     * The round this proposal was handed in for. A real association, so counting a body's proposals in a period is a
     * matter of following it; the previous design had none and inferred period membership from creation timestamps.
     */
    #[ManyToOne(
        targetEntity: OptionPeriod::class,
        inversedBy: 'proposals',
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private OptionPeriod $period;

    /**
     * The body hosting the activity, or null when the board is hosting it itself. The board is not a body, so it
     * cannot be named here, and it is not held to a proposal limit either.
     */
    #[ManyToOne(targetEntity: Organ::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?Organ $organ = null;

    /**
     * A working title. Everyone reads it on the calendar, so it has to say what the activity is.
     */
    #[Column(
        type: Types::STRING,
        length: 128,
    )]
    private string $name;

    /**
     * Anything the board should know while deciding, such as a dependency on somebody outside the association.
     */
    #[Column(
        type: Types::TEXT,
        nullable: true,
    )]
    private ?string $description = null;

    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private Member $createdBy;

    #[Column(
        type: Types::STRING,
        length: 32,
        enumType: ProposalStatus::class,
    )]
    private ProposalStatus $status = ProposalStatus::Submitted;

    /** @var Collection<array-key, ActivityDateOption> */
    #[OneToMany(
        targetEntity: ActivityDateOption::class,
        mappedBy: 'proposal',
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[OrderBy(['position' => 'ASC'])]
    private Collection $dateOptions;

    /**
     * The date the board reserved. A unique association rather than a status anybody has to count, so a proposal
     * cannot end up holding two dates however the transitions are applied.
     */
    #[OneToOne(targetEntity: ActivityDateOption::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?ActivityDateOption $chosenOption = null;

    /**
     * The activity this proposal became, started off as a draft the moment a date was reserved.
     *
     * `SET NULL` on delete because abandoned drafts really are removed
     * ({@see \App\Command\Activity\DeleteStaleDraftsCommand}), and the proposal has to survive that: it is the record
     * of who held the date.
     */
    #[OneToOne(targetEntity: Activity::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?Activity $activity = null;

    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?Member $decidedBy = null;

    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $decidedAt = null;

    /**
     * How the financial side was settled, or null while it has not been. Null is what the reminder and the lapse
     * chase; either outcome stops them.
     */
    #[Column(
        type: Types::STRING,
        length: 32,
        nullable: true,
        enumType: BudgetClearance::class,
    )]
    private ?BudgetClearance $budgetClearance = null;

    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?Member $budgetClearedBy = null;

    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $budgetClearedAt = null;

    /**
     * When the body was last told the date is at risk, so a nightly run does not tell them again every night.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $budgetRemindedAt = null;

    public function __construct()
    {
        $this->dateOptions = new ArrayCollection();
    }

    public function getPeriod(): OptionPeriod
    {
        return $this->period;
    }

    public function setPeriod(OptionPeriod $period): void
    {
        $this->period = $period;
    }

    public function getOrgan(): ?Organ
    {
        return $this->organ;
    }

    public function setOrgan(?Organ $organ): void
    {
        $this->organ = $organ;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedBy(): Member
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Member $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getStatus(): ProposalStatus
    {
        return $this->status;
    }

    public function setStatus(ProposalStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection<array-key, ActivityDateOption>
     */
    public function getDateOptions(): Collection
    {
        return $this->dateOptions;
    }

    public function addDateOption(ActivityDateOption $dateOption): void
    {
        if ($this->dateOptions->contains($dateOption)) {
            return;
        }

        $this->assertRoomForAnotherDateOption();

        $this->dateOptions->add($dateOption);
        $dateOption->setProposal($this);
    }

    public function removeDateOption(ActivityDateOption $dateOption): void
    {
        $this->dateOptions->removeElement($dateOption);
    }

    /**
     * The dates that still stand in somebody else's way, in the body's own order of preference.
     *
     * @return ActivityDateOption[]
     */
    public function getStandingDateOptions(): array
    {
        return $this->dateOptions
            ->filter(static fn (ActivityDateOption $option): bool => $option->getStatus()->isStanding())
            ->getValues();
    }

    public function getChosenOption(): ?ActivityDateOption
    {
        return $this->chosenOption;
    }

    public function setChosenOption(?ActivityDateOption $chosenOption): void
    {
        $this->chosenOption = $chosenOption;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): void
    {
        $this->activity = $activity;
    }

    public function getDecidedBy(): ?Member
    {
        return $this->decidedBy;
    }

    public function setDecidedBy(?Member $decidedBy): void
    {
        $this->decidedBy = $decidedBy;
    }

    public function getDecidedAt(): ?DateTime
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(?DateTime $decidedAt): void
    {
        $this->decidedAt = $decidedAt;
    }

    public function getBudgetClearance(): ?BudgetClearance
    {
        return $this->budgetClearance;
    }

    public function setBudgetClearance(?BudgetClearance $budgetClearance): void
    {
        $this->budgetClearance = $budgetClearance;
    }

    public function getBudgetClearedBy(): ?Member
    {
        return $this->budgetClearedBy;
    }

    public function setBudgetClearedBy(?Member $budgetClearedBy): void
    {
        $this->budgetClearedBy = $budgetClearedBy;
    }

    public function getBudgetClearedAt(): ?DateTime
    {
        return $this->budgetClearedAt;
    }

    public function setBudgetClearedAt(?DateTime $budgetClearedAt): void
    {
        $this->budgetClearedAt = $budgetClearedAt;
    }

    public function getBudgetRemindedAt(): ?DateTime
    {
        return $this->budgetRemindedAt;
    }

    public function setBudgetRemindedAt(?DateTime $budgetRemindedAt): void
    {
        $this->budgetRemindedAt = $budgetRemindedAt;
    }

    /**
     * Whether the financial side has been settled, either way.
     */
    public function isBudgetCleared(): bool
    {
        return null !== $this->budgetClearance;
    }

    /**
     * Turn every date that was not picked down, which is what releases those dates for whoever is next in line.
     */
    public function declineDateOptionsOtherThan(?ActivityDateOption $keep): void
    {
        foreach ($this->dateOptions as $dateOption) {
            if ($dateOption === $keep) {
                continue;
            }

            $dateOption->setStatus(DateOptionStatus::Declined);
        }
    }

    /**
     * A proposal is handed in with its dates in one go, so the count is settled at insert.
     *
     * There is deliberately no `PreUpdate` counterpart: Doctrine only raises that event when a field of the entity
     * itself changed, so an edit that only added a date would slip past it. Editing goes through the form, where
     * `Count` says the same thing and can point at the field that is wrong.
     */
    #[PrePersist]
    public function assertDateOptionCount(): void
    {
        $count = count($this->dateOptions);

        if (
            $count >= 1
            && $count <= self::MAX_DATE_OPTIONS
        ) {
            return;
        }

        throw new LogicException(sprintf(
            'A proposal must put forward between 1 and %d dates, got %d.',
            self::MAX_DATE_OPTIONS,
            $count,
        ));
    }

    private function assertRoomForAnotherDateOption(): void
    {
        if (count($this->dateOptions) < self::MAX_DATE_OPTIONS) {
            return;
        }

        throw new LogicException(sprintf(
            'A proposal cannot put forward more than %d dates.',
            self::MAX_DATE_OPTIONS,
        ));
    }
}
