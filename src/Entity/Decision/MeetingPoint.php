<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\Decision\MeetingPointRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * An agenda point of a meeting, under which documents are filed.
 *
 * There is deliberately no relation to {@see Decision}: agenda points can shift during the actual meeting, so decisions
 * are matched to points at render time by comparing `Decision::$point` against the leading integer of the free-form
 * number. The board corrects a shifted agenda by renumbering points afterwards.
 */
#[Entity(repositoryClass: MeetingPointRepository::class)]
#[HasLifecycleCallbacks]
class MeetingPoint
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[ManyToOne(
        targetEntity: Meeting::class,
        inversedBy: 'points',
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
     * Free-form agenda point number, e.g. "7" or "7a". Gaps and duplicates are allowed; duplicates surface as a
     * readiness warning on the management page.
     */
    #[Column(
        type: Types::STRING,
        length: 16,
    )]
    private string $number;

    #[Column(type: Types::STRING)]
    private string $title = '';

    /**
     * Determines the order in which to display the agenda point.
     */
    #[Column(
        type: Types::INTEGER,
        options: ['default' => 0],
    )]
    private int $displayPosition = 0;

    /**
     * Documents filed under this agenda point.
     *
     * @var Collection<array-key, MeetingDocument>
     */
    #[OneToMany(
        targetEntity: MeetingDocument::class,
        mappedBy: 'point',
    )]
    #[OrderBy(value: ['displayPosition' => 'ASC'])]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $meeting->addPoint($this);
        $this->meeting = $meeting;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDisplayPosition(): int
    {
        return $this->displayPosition;
    }

    public function setDisplayPosition(int $position): void
    {
        $this->displayPosition = $position;
    }

    /**
     * @return Collection<array-key, MeetingDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }
}
