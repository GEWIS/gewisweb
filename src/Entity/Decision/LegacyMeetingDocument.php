<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\Decision\LegacyMeetingDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * A meeting document from before documents had agenda points and versions. The table is the renamed original
 * `MeetingDocument` and is read only by the one-shot data migrator; it is dropped once the migration has been verified
 * in production.
 *
 * The meeting is unidirectional: `Meeting::$documents` belongs to the new model.
 *
 * @internal
 */
#[Entity(repositoryClass: LegacyMeetingDocumentRepository::class)]
#[HasLifecycleCallbacks]
class LegacyMeetingDocument
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[ManyToOne(targetEntity: Meeting::class)]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
    )]
    private Meeting $meeting;

    #[Column(type: Types::STRING)]
    private string $name;

    #[Column(type: Types::STRING)]
    private string $path;

    #[Column(
        type: Types::INTEGER,
        options: ['default' => 0],
    )]
    private int $displayPosition = 0;

    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getDisplayPosition(): int
    {
        return $this->displayPosition;
    }

    public function setDisplayPosition(int $displayPosition): void
    {
        $this->displayPosition = $displayPosition;
    }
}
