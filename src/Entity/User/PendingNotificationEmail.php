<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Notification;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\User\PendingNotificationEmailRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * A notification queued to be emailed to a member, awaiting their next digest. The email channel enqueues one per
 * opted-in member when a notification is published; the digest job drains them per member at their chosen frequency.
 */
#[Entity(repositoryClass: PendingNotificationEmailRepository::class)]
class PendingNotificationEmail
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

    #[ManyToOne(targetEntity: Notification::class)]
    #[JoinColumn(
        name: 'notification_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private Notification $notification;

    public function __construct(
        User $user,
        Notification $notification,
    ) {
        $this->user = $user;
        $this->notification = $notification;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
