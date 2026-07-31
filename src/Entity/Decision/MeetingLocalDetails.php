<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Enums\MeetingTypes;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Locally-owned details of a meeting. The {@see Meeting} itself is a read-only GEWISDB replica (only the date is
 * synced), so anything the board manages on the website lives here.
 */
#[Entity]
#[HasLifecycleCallbacks]
class MeetingLocalDetails
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

    #[OneToOne(
        targetEntity: Meeting::class,
        inversedBy: 'localDetails',
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
     * The time the meeting starts. Meetings have no end time; they run until closed.
     */
    #[Column(
        type: Types::TIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $startTime = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $location = null;

    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
    }

    public function getStartTime(): ?DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(?DateTime $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }
}
