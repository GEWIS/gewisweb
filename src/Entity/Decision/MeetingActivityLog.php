<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Enums\MeetingActivityVerbs;
use App\Entity\User\User;
use App\Repository\Decision\MeetingActivityLogRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * One entry of the meeting-management activity feed. Entries without a meeting belong to the reference library.
 */
#[Entity(repositoryClass: MeetingActivityLogRepository::class)]
#[Index(
    name: 'meeting_activity_log_created_idx',
    columns: ['createdAt'],
)]
class MeetingActivityLog
{
    use IdentifiableTrait;

    /**
     * The account that performed the action; `null` for actions by the system.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'actor',
        referencedColumnName: 'lidnr',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?User $actor = null;

    #[ManyToOne(targetEntity: Meeting::class)]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
    )]
    private ?Meeting $meeting = null;

    #[Column(type: Types::ENUM)]
    private MeetingActivityVerbs $verb;

    /**
     * What the action applied to, e.g. the document name with its version label.
     */
    #[Column(type: Types::STRING)]
    private string $subject;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): void
    {
        $this->actor = $actor;
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(?Meeting $meeting): void
    {
        $this->meeting = $meeting;
    }

    public function getVerb(): MeetingActivityVerbs
    {
        return $this->verb;
    }

    public function setVerb(MeetingActivityVerbs $verb): void
    {
        $this->verb = $verb;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
