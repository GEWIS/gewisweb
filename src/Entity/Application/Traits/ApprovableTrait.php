<?php

declare(strict_types=1);

namespace App\Entity\Application\Traits;

use App\Entity\Application\ApprovableText as ApprovableTextModel;
use App\Entity\Application\Enums\ApprovableStatus;
use App\Entity\Decision\Member as MemberModel;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * A trait which provides basic (repeated) functionality for approvable entities.
 *
 * TODO: Make activities also use this trait.
 *
 * @psalm-type ApprovableTraitGdprArrayType = array{
 *     id: int,
 *     approved: int,
 *     approvedAt: ?string,
 *     approvableText: ?string,
 * }
 */
trait ApprovableTrait
{
    /**
     * State of the approval.
     */
    #[Column(
        type: Types::INTEGER,
        enumType: ApprovableStatus::class,
    )]
    private ApprovableStatus $approved;

    /**
     * The date when the entity was approved.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $approvedAt = null;

    /**
     * Who (dis)approved the entity using this trait?
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    private ?MemberModel $approver = null;

    /**
     * When the entity has been approved/rejected a message can be attached. Since we do not always need this message it
     * has been replaced with another entity which we can EXTRA_LAZY load to ensure it is not always included.
     */
    #[OneToOne(
        targetEntity: ApprovableTextModel::class,
        cascade: [
            'persist',
            'remove',
        ],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'approvableText_id',
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?ApprovableTextModel $approvableText = null;

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

    /**
     * @return ApprovableTraitGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'approved' => $this->getApproved()->value,
            'approvedAt' => $this->getApprovedAt()?->format(DateTimeInterface::ATOM),
            'approvableText' => $this->getApprovableText()?->getMessage(),
        ];
    }
}
