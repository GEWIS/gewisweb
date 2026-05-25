<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Repository\Decision\MeetingMinutesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Meeting minutes.
 */
#[Entity(repositoryClass: MeetingMinutesRepository::class)]
#[HasLifecycleCallbacks]
class MeetingMinutes
{
    use TimestampableTrait;

    /**
     * Meeting type.
     */
    #[Id]
    #[Column(type: Types::ENUM)]
    private MeetingTypes $meeting_type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: Types::INTEGER)]
    private int $meeting_number;

    /**
     * The corresponding meeting for these minutes.
     */
    #[OneToOne(
        targetEntity: Meeting::class,
        inversedBy: 'meetingMinutes',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
        nullable: false,
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
        nullable: false,
    )]
    private Meeting $meeting;

    /**
     * The storage path.
     */
    #[Column(type: Types::STRING)]
    private string $path;

    public function getPath(): string
    {
        return $this->path;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'meeting_minutes';
    }
}
