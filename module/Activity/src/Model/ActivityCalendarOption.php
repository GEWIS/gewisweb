<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Activity calendar option model.
 */
#[Entity]
class ActivityCalendarOption
{
    use IdentifiableTrait;

    /**
     * Type for the option.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $type;

    /**
     * Status for the option.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $status;

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
     * To what activity proposal does the option belong.
     */
    #[ManyToOne(targetEntity: ActivityOptionProposal::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityOptionProposal $proposal;

    /**
     * Who modified this activity option, if null then the option is not modified.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    protected ?MemberModel $modifiedBy;

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
