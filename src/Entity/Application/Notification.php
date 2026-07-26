<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Application\NotificationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * A published notification shown in the notification centre. It records only what happened and what it happened to;
 * the sentence, the link and its label all come from {@see NotificationType}, so a notification follows its subject
 * when that is renamed and reads in the language of whoever is looking at it.
 *
 * There is no per-user read state; unread is derived per member from UserSettings::$notificationsReadAt, so one row
 * serves everyone instead of fanning out into a row per member.
 */
#[Entity(repositoryClass: NotificationRepository::class)]
#[UniqueConstraint(
    name: 'notification_type_subject',
    columns: [
        'type',
        'subjectId',
    ],
)]
class Notification
{
    use IdentifiableTrait;

    #[Column(
        type: Types::STRING,
        length: 32,
        enumType: NotificationType::class,
    )]
    private NotificationType $type;

    /**
     * The primary key of whatever this notification is about, read through {@see NotificationType}. A subject may
     * become public more than once (an album taken offline and put back), so at most one notification exists per
     * subject and type. Null for a notification that stands on its own, which the constraint leaves unconstrained
     * because the database treats nulls as distinct.
     */
    #[Column(
        type: Types::INTEGER,
        nullable: true,
    )]
    private ?int $subjectId = null;

    #[Column(
        type: Types::STRING,
        length: 16,
        enumType: AlertTypes::class,
    )]
    private AlertTypes $level = AlertTypes::Info;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): void
    {
        $this->type = $type;
    }

    public function getSubjectId(): ?int
    {
        return $this->subjectId;
    }

    public function setSubjectId(?int $subjectId): void
    {
        $this->subjectId = $subjectId;
    }

    public function getLevel(): AlertTypes
    {
        return $this->level;
    }

    public function setLevel(AlertTypes $level): void
    {
        $this->level = $level;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
