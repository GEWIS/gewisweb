<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\DateOptionStatus;
use App\Entity\Activity\Enums\TimeOfDay;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member;
use App\Repository\Activity\ActivityDateOptionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * One of the dates a body put forward for a proposed activity.
 *
 * Whole days, not clock times: the option calendar reserves a date, and which part of the day is wanted is what
 * {@see self::$timeOfDay} says. A body may put forward up to three of these per proposal and the board picks one.
 */
#[Entity(repositoryClass: ActivityDateOptionRepository::class)]
#[Index(
    fields: [
        'beginsAt',
        'endsAt',
    ],
    name: 'activity_date_option_span',
)]
class ActivityDateOption
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: ActivityProposal::class,
        inversedBy: 'dateOptions',
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityProposal $proposal;

    /**
     * The first day the activity would take place on.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $beginsAt;

    /**
     * The last day the activity would take place on, the same as {@see self::$beginsAt} for anything within one day.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $endsAt;

    #[Column(
        type: Types::STRING,
        length: 32,
        enumType: TimeOfDay::class,
    )]
    private TimeOfDay $timeOfDay = TimeOfDay::Evening;

    /**
     * Where this date sits in the body's own order of preference, counting from one. The board is not bound by it,
     * but it is what the body would rather have.
     */
    #[Column(type: Types::SMALLINT)]
    private int $position = 1;

    #[Column(
        type: Types::STRING,
        length: 32,
        enumType: DateOptionStatus::class,
    )]
    private DateOptionStatus $status = DateOptionStatus::Proposed;

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

    public function getProposal(): ActivityProposal
    {
        return $this->proposal;
    }

    public function setProposal(ActivityProposal $proposal): void
    {
        $this->proposal = $proposal;
    }

    public function getBeginsAt(): DateTime
    {
        return $this->beginsAt;
    }

    public function setBeginsAt(DateTime $beginsAt): void
    {
        $this->beginsAt = $beginsAt;
    }

    public function getEndsAt(): DateTime
    {
        return $this->endsAt;
    }

    public function setEndsAt(DateTime $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    public function getTimeOfDay(): TimeOfDay
    {
        return $this->timeOfDay;
    }

    public function setTimeOfDay(TimeOfDay $timeOfDay): void
    {
        $this->timeOfDay = $timeOfDay;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getStatus(): DateOptionStatus
    {
        return $this->status;
    }

    public function setStatus(DateOptionStatus $status): void
    {
        $this->status = $status;
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

    /**
     * Whether this option takes up the given day, which for anything spanning several days is every day in between.
     */
    public function coversDay(DateTimeInterface $day): bool
    {
        return $this->getBeginsAt()->format('Y-m-d') <= $day->format('Y-m-d')
            && $this->getEndsAt()->format('Y-m-d') >= $day->format('Y-m-d');
    }

    public function spansMultipleDays(): bool
    {
        return $this->getBeginsAt()->format('Y-m-d') !== $this->getEndsAt()->format('Y-m-d');
    }
}
