<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Repository\Decision\MeetingMinutesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * Meeting minutes: at most one per meeting, with the actual files as {@see MeetingMinutesVersion}s. Uploading minutes
 * marks the meeting complete for members, even when no decisions were taken.
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
     * The versions of the minutes, in upload order; members see the last one.
     *
     * @var Collection<array-key, MeetingMinutesVersion>
     */
    #[OneToMany(
        targetEntity: MeetingMinutesVersion::class,
        mappedBy: 'minutes',
    )]
    #[OrderBy(value: ['id' => 'ASC'])]
    private Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $meeting->setMeetingMinutes($this);
        $this->meeting = $meeting;
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
    }

    /**
     * @return Collection<array-key, MeetingMinutesVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(MeetingMinutesVersion $version): void
    {
        $this->versions[] = $version;
    }

    public function getLatestVersion(): ?MeetingMinutesVersion
    {
        $latest = $this->versions->last();

        return false === $latest
            ? null
            : $latest;
    }
}
