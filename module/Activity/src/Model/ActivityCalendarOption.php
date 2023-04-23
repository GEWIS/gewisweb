<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
};

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
        type: "string",
        nullable: true,
    )]
    protected ?string $type;

    /**
     * Status for the option.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $status;

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
     * To what activity proposal does the option belong.
     */
    #[ManyToOne(targetEntity: ActivityOptionProposal::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected ActivityOptionProposal $proposal;

    /**
     * Who modified this activity option, if null then the option is not modified.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?MemberModel $modifiedBy;

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
     * @return MemberModel|null
     */
    public function getModifiedBy(): ?MemberModel
    {
        return $this->modifiedBy;
    }

    /**
     * @param MemberModel|null $modifiedBy
     */
    public function setModifiedBy(?MemberModel $modifiedBy): void
    {
        $this->modifiedBy = $modifiedBy;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns the string identifier of the Resource.
     *
     * @return int|string
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
     *
     * @return string
     */
    public function getCreatorAlt(): string
    {
        return $this->getProposal()->getCreatorAlt();
    }

    /**
     * @return ActivityOptionProposal
     */
    public function getProposal(): ActivityOptionProposal
    {
        return $this->proposal;
    }

    /**
     * @param ActivityOptionProposal $proposal
     */
    public function setProposal(ActivityOptionProposal $proposal): void
    {
        $this->proposal = $proposal;
    }
}
