<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\User\CompanyUser as CompanyUserModel;
use App\Entity\User\User as UserModel;
use App\Repository\Application\EditLockRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * An exclusive edit lock on a revisable aggregate (an activity, vacancy or company), keyed by its resource id + key so
 * a single generic lock covers every revisable domain. It is held by whoever is editing and kept alive by heartbeat
 * pings ({@see $lastPingAt}); once a ping is missed for long enough the lock is considered expired and can be taken
 * over, so an abandoned editor frees it with no cron. {@see \App\Service\Application\EditLockService} drives it.
 */
#[Entity(repositoryClass: EditLockRepository::class)]
#[UniqueConstraint(
    name: 'edit_lock_resource_uniq',
    columns: [
        'resourceId',
        'resourceKey',
    ],
)]
class EditLock
{
    use IdentifiableTrait;

    /**
     * The resource type, e.g. 'activity' / 'vacancy' / 'company' (a {@see RevisableInterface::getResourceId()}).
     */
    #[Column(
        type: Types::STRING,
        length: 32,
    )]
    private string $resourceId;

    /**
     * The aggregate's primary key within that resource type.
     */
    #[Column(type: Types::INTEGER)]
    private int $resourceKey;

    /**
     * The user (a member's account) holding the lock. Mutually exclusive with {@see $lockedByCompanyUser}.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?UserModel $lockedBy = null;

    /**
     * The company user holding the lock (careers portal). Mutually exclusive with {@see $lockedBy}.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?CompanyUserModel $lockedByCompanyUser = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $acquiredAt;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $lastPingAt;

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getResourceKey(): int
    {
        return $this->resourceKey;
    }

    public function setResourceKey(int $resourceKey): void
    {
        $this->resourceKey = $resourceKey;
    }

    public function getLockedBy(): ?UserModel
    {
        return $this->lockedBy;
    }

    public function setLockedBy(?UserModel $lockedBy): void
    {
        $this->lockedBy = $lockedBy;
    }

    public function getLockedByCompanyUser(): ?CompanyUserModel
    {
        return $this->lockedByCompanyUser;
    }

    public function setLockedByCompanyUser(?CompanyUserModel $lockedByCompanyUser): void
    {
        $this->lockedByCompanyUser = $lockedByCompanyUser;
    }

    public function getAcquiredAt(): DateTime
    {
        return $this->acquiredAt;
    }

    public function setAcquiredAt(DateTime $acquiredAt): void
    {
        $this->acquiredAt = $acquiredAt;
    }

    public function getLastPingAt(): DateTime
    {
        return $this->lastPingAt;
    }

    public function setLastPingAt(DateTime $lastPingAt): void
    {
        $this->lastPingAt = $lastPingAt;
    }

    /**
     * A human-readable name for whoever holds the lock, whether a member's account or a company user.
     */
    public function getHolderDisplayName(): string
    {
        return $this->lockedBy?->getDisplayName()
            ?? $this->lockedByCompanyUser?->getDisplayName()
            ?? '';
    }
}
