<?php

namespace Application\Model\Traits;

use Application\Model\ApprovableText as ApprovableTextModel;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    JoinColumn,
    ManyToOne,
    OneToOne,
};

/**
 * A trait which provides basic (repeated) functionality for approvable entities.
 *
 * TODO: Make activities also use this trait.
 */
trait ApprovableTrait
{
    /**
     * State of the approval.
     */
    #[Column(
        type: "integer",
        // TODO: Change to ORM supported enum when ORM v2.11.0 is released:
        // TODO: enumType: ApprovableStatus::class,
    )]
    protected int $approved;

    /**
     * The date when the entity was approved.
     */
    #[Column(
        type: "datetime",
        nullable: true,
    )]
    protected ?DateTime $approvedAt = null;

    /**
     * Who (dis)approved the entity using this trait?
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?MemberModel $approver = null;

    /**
     * When the entity has been approved/rejected a message can be attached. Since we do not always need this message it
     * has been replaced with another entity which we can EXTRA_LAZY load to ensure it is not always included.
     */
    #[OneToOne(
        targetEntity: ApprovableTextModel::class,
        fetch: "EXTRA_LAZY",
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "approvableText_id",
        referencedColumnName: "id",
        nullable: true,
    )]
    protected ?ApprovableTextModel $approvableText = null;

    /**
     * TODO: Change to {@link ApprovableStatus}.
     *
     * @return int
     */
    public function getApproved(): int
    {
        return $this->approved;
    }

    /**
     * TODO: Change to {@link ApprovableStatus}.
     *
     * @param int $approved
     */
    public function setApproved(int $approved): void
    {
        $this->approved = $approved;
    }

    /**
     * @return DateTime|null
     */
    public function getApprovedAt(): ?DateTime
    {
        return $this->approvedAt;
    }

    /**
     * @param DateTime|null $approvedAt
     */
    public function setApprovedAt(?DateTime $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    /**
     * @return MemberModel|null
     */
    public function getApprover(): ?MemberModel
    {
        return $this->approver;
    }

    /**
     * @param MemberModel|null $approver
     */
    public function setApprover(?MemberModel $approver): void
    {
        $this->approver = $approver;
    }

    /**
     * @return ApprovableTextModel
     */
    public function getApprovableText(): ApprovableTextModel
    {
        return $this->approvableText;
    }

    /**
     * @param ApprovableTextModel $approvableText
     */
    public function setApprovableText(ApprovableTextModel $approvableText): void
    {
        $this->approvableText = $approvableText;
    }
}
