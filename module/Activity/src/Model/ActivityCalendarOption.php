<?php

namespace Activity\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};
use User\Model\User as UserModel;

/**
 * Activity calendar option model.
 */
#[Entity]
class ActivityCalendarOption
{
    /**
     * ID for the option.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Type for the option.
     */
    #[Column(
        type: "string",
        nullable: true)
    ]
    protected ?string $type;

    /**
     * Status for the option.
     */
    #[Column(
        type: "string",
        nullable: true)
    ]
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
    #[ManyToOne(targetEntity: "Activity\Model\ActivityOptionProposal")]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected ActivityOptionProposal $proposal;

    /**
     * Who modified this activity option, if null then the option is not modified.
     */
    #[ManyToOne(targetEntity: "User\Model\User")]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?UserModel $modifiedBy;

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
     * @return UserModel|null
     */
    public function getModifiedBy(): ?UserModel
    {
        return $this->modifiedBy;
    }

    /**
     * @param UserModel|null $modifiedBy
     */
    public function setModifiedBy(?UserModel $modifiedBy): void
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns in order of presense:
     * 1. The abbreviation of the related organ
     * 2. The alternative for an organ, other organising parties
     * 3. The full name of the member who created the proposal.
     *
     * @return mixed
     */
    public function getCreatorAlt()
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
