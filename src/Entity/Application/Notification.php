<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\Application\NotificationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

/**
 * A published notification shown in the notification centre. It records only what happened and what it happened to;
 * the sentence, the link and its label all come from {@see NotificationType}, so a notification follows its subject
 * when that is renamed and reads in the language of whoever is looking at it.
 *
 * Most notifications go to everyone, and one row then serves the whole membership. A notification may instead be
 * addressed to a single user, which is what anything about their own account has to be.
 *
 * There is no per-user read state either way; unread is derived per member from UserSettings::$notificationsReadAt.
 */
#[Entity(repositoryClass: NotificationRepository::class)]
#[UniqueConstraint(
    name: 'notification_type_subject',
    columns: [
        'type',
        'subjectId',
    ],
)]
#[Index(
    fields: [
        'recipientUser',
        'createdAt',
    ],
    name: 'notification_recipient_user',
)]
#[Index(
    fields: ['createdAt'],
    name: 'notification_created_at',
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

    /**
     * What a notification with no subject to point at has to say for itself, frozen when it was published. Such a
     * notification outlives whatever it describes (someone signs in and then signs out again), so it cannot be left to
     * look anything up later.
     *
     * The keys mean whatever {@see NotificationType} says they mean; a sign-in keeps the parts of the device it came
     * from. Parts rather than a finished sentence, because the words joining them are translated when read.
     *
     * @var array<string, string>|null
     */
    #[Column(
        type: Types::JSON,
        nullable: true,
    )]
    private ?array $context = null;

    /**
     * Who this notification is addressed to, or nobody in particular when both are null and it goes to everyone.
     *
     * The two kinds of account get a column each so the database can hold a real foreign key to either, which a single
     * identifier could not. At most one is ever set.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'recipientUser',
        referencedColumnName: 'lidnr',
        nullable: true,
        onDelete: 'CASCADE',
    )]
    private ?User $recipientUser = null;

    #[ManyToOne(targetEntity: CompanyUser::class)]
    #[JoinColumn(
        name: 'recipientCompanyUser',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE',
    )]
    private ?CompanyUser $recipientCompanyUser = null;

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

    /**
     * @return array<string, string>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, string>|null $context
     */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function getRecipientUser(): ?User
    {
        return $this->recipientUser;
    }

    public function getRecipientCompanyUser(): ?CompanyUser
    {
        return $this->recipientCompanyUser;
    }

    public function hasRecipient(): bool
    {
        return null !== $this->recipientUser
            || null !== $this->recipientCompanyUser;
    }

    /**
     * Set through one call so a notification can never end up addressed to two accounts at once.
     */
    public function setRecipient(
        ?User $user,
        ?CompanyUser $companyUser,
    ): void {
        if (
            null !== $user
            && null !== $companyUser
        ) {
            throw new InvalidArgumentException('A notification is addressed to one account, not two.');
        }

        $this->recipientUser = $user;
        $this->recipientCompanyUser = $companyUser;
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
