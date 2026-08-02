<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Repository\Decision\LegacyMeetingMinutesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Meeting minutes from before minutes had versions. The table is the renamed original `MeetingMinutes` and is read
 * only by the one-shot data migrator; it is dropped once the migration has been verified in production.
 *
 * The meeting is unidirectional: `Meeting::$meetingMinutes` belongs to the new model.
 *
 * @internal
 */
#[Entity(repositoryClass: LegacyMeetingMinutesRepository::class)]
#[HasLifecycleCallbacks]
class LegacyMeetingMinutes
{
    use TimestampableTrait;

    #[Id]
    #[Column(type: Types::ENUM)]
    private MeetingTypes $meeting_type;

    #[Id]
    #[Column(type: Types::INTEGER)]
    private int $meeting_number;

    #[OneToOne(targetEntity: Meeting::class)]
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

    #[Column(type: Types::STRING)]
    private string $path;

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

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }
}
