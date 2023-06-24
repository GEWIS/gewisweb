<?php

declare(strict_types=1);

namespace Application\Model\Traits;

use Application\Model\ApprovableText as ApprovableTextModel;
use Application\Model\Enums\ApprovableStatus;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

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
        type: 'integer',
        enumType: ApprovableStatus::class,
    )]
    protected ApprovableStatus $approved;

    /**
     * The date when the entity was approved.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $approvedAt = null;

    /**
     * Who (dis)approved the entity using this trait?
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    protected ?MemberModel $approver = null;

    /**
     * When the entity has been approved/rejected a message can be attached. Since we do not always need this message it
     * has been replaced with another entity which we can EXTRA_LAZY load to ensure it is not always included.
     */
    #[OneToOne(
        targetEntity: ApprovableTextModel::class,
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'approvableText_id',
        referencedColumnName: 'id',
        nullable: true,
    )]
    protected ?ApprovableTextModel $approvableText = null;

    public function getApproved(): ApprovableStatus
    {
        return $this->approved;
    }

    public function isApproved(): bool
    {
        return ApprovableStatus::Approved === $this->getApproved();
    }

    public function setApproved(ApprovableStatus $approved): void
    {
        $this->approved = $approved;
    }

    public function getApprovedAt(): ?DateTime
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?DateTime $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    public function getApprover(): ?MemberModel
    {
        return $this->approver;
    }

    public function setApprover(?MemberModel $approver): void
    {
        $this->approver = $approver;
    }

    public function getApprovableText(): ?ApprovableTextModel
    {
        return $this->approvableText;
    }

    public function setApprovableText(?ApprovableTextModel $approvableText): void
    {
        $this->approvableText = $approvableText;
    }
}
