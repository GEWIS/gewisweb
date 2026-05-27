<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Repository\Activity\ActivityCalendarOptionRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Activity calendar option model.
 */
#[Entity(repositoryClass: ActivityCalendarOptionRepository::class)]
class ActivityCalendarOption
{
    use IdentifiableTrait;

    /**
     * Type for the option.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $type;

    /**
     * Status for the option.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $status;

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
     * To what activity proposal does the option belong.
     */
    #[ManyToOne(targetEntity: ActivityOptionProposal::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityOptionProposal $proposal;

    /**
     * Who modified this activity option, if null then the option is not modified.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    private ?MemberModel $modifiedBy;

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

    public function getModifiedBy(): ?MemberModel
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?MemberModel $modifiedBy): void
    {
        $this->modifiedBy = $modifiedBy;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns the string identifier of the Resource.
     */
    public function getResourceId(): int|string
    {
        return $this->getId();
    }

    /**
     * Returns in order of presense:
     * 1. The abbreviation of the related organ
     * 2. The alternative for an organ, other organising parties
     * 3. The full name of the member who created the proposal.
     */
    public function getCreatorAlt(): string
    {
        return $this->getProposal()->getCreatorAlt();
    }

    public function getProposal(): ActivityOptionProposal
    {
        return $this->proposal;
    }

    public function setProposal(ActivityOptionProposal $proposal): void
    {
        $this->proposal = $proposal;
    }
}
