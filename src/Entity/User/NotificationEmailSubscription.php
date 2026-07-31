<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Enums\NotificationEmailFrequency;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\User\NotificationEmailSubscriptionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * A member's opt-in to receive email for one notification category, together with how often those emails should be
 * batched. A row present means "email me about this category"; a category the member has not enabled simply has no row.
 * Website notifications are always on and are not represented here.
 */
#[Entity(repositoryClass: NotificationEmailSubscriptionRepository::class)]
#[UniqueConstraint(columns: [
    'user_id',
    'category',
])]
class NotificationEmailSubscription
{
    use IdentifiableTrait;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'user_id',
        referencedColumnName: 'lidnr',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private User $user;

    #[Column(
        type: Types::STRING,
        length: 64,
        enumType: NotificationType::class,
    )]
    private NotificationType $category;

    #[Column(
        type: Types::STRING,
        length: 16,
        enumType: NotificationEmailFrequency::class,
        options: ['default' => 'immediately'],
    )]
    private NotificationEmailFrequency $frequency;

    /**
     * When this category's digest was last mailed to the member. Null means it never has been, so the next run is due.
     * Gates how often the digest job mails this category, per {@see $frequency}.
     */
    #[Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
    )]
    private ?DateTimeImmutable $lastSentAt = null;

    public function __construct(
        User $user,
        NotificationType $category,
        NotificationEmailFrequency $frequency = NotificationEmailFrequency::Immediately,
    ) {
        $this->user = $user;
        $this->category = $category;
        $this->frequency = $frequency;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCategory(): NotificationType
    {
        return $this->category;
    }

    public function getFrequency(): NotificationEmailFrequency
    {
        return $this->frequency;
    }

    public function setFrequency(NotificationEmailFrequency $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getLastSentAt(): ?DateTimeImmutable
    {
        return $this->lastSentAt;
    }

    public function setLastSentAt(?DateTimeImmutable $lastSentAt): void
    {
        $this->lastSentAt = $lastSentAt;
    }
}
