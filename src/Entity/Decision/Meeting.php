<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\SubDecision\Minutes;
use App\Repository\Decision\MeetingRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

use function sprintf;

/**
 * Meeting model.
 */
#[Entity(repositoryClass: MeetingRepository::class)]
class Meeting
{
    /**
     * Meeting type.
     */
    #[Id]
    #[Column(type: Types::ENUM)]
    private MeetingTypes $type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: Types::INTEGER)]
    private int $number;

    /**
     * Meeting date.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $date;

    /**
     * Decisions.
     *
     * @var Collection<array-key, Decision>
     */
    #[OneToMany(
        targetEntity: Decision::class,
        mappedBy: 'meeting',
    )]
    private Collection $decisions;

    #[OneToOne(
        targetEntity: Minutes::class,
        mappedBy: 'meeting',
    )]
    private Minutes $minutes;

    /**
     * Agenda points.
     *
     * @var Collection<array-key, MeetingPoint>
     */
    #[OneToMany(
        targetEntity: MeetingPoint::class,
        mappedBy: 'meeting',
    )]
    #[OrderBy(value: ['displayPosition' => 'ASC'])]
    private Collection $points;

    /**
     * Documents.
     *
     * @var Collection<array-key, MeetingDocument>
     */
    #[OneToMany(
        targetEntity: MeetingDocument::class,
        mappedBy: 'meeting',
    )]
    #[OrderBy(value: ['displayPosition' => 'ASC'])]
    private Collection $documents;

    /**
     * The minutes for this meeting.
     */
    #[OneToOne(
        targetEntity: MeetingMinutes::class,
        mappedBy: 'meeting',
    )]
    private ?MeetingMinutes $meetingMinutes = null;

    /**
     * The locally-owned details of this meeting.
     */
    #[OneToOne(
        targetEntity: MeetingLocalDetails::class,
        mappedBy: 'meeting',
    )]
    private ?MeetingLocalDetails $localDetails = null;

    public function __construct()
    {
        $this->decisions = new ArrayCollection();
        $this->points = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * Get the meeting type.
     */
    public function getType(): MeetingTypes
    {
        return $this->type;
    }

    /**
     * Get the meeting number.
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Set the meeting type.
     */
    public function setType(MeetingTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Set the meeting number.
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * The per-meeting segment of the scoped storage namespaces, e.g. `ALV-42`.
     */
    public function getStorageScope(): string
    {
        return sprintf(
            '%s-%d',
            $this->type->value,
            $this->number,
        );
    }

    /**
     * Get the meeting date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the meeting date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the decisions.
     *
     * @return Collection<array-key, Decision>
     */
    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    /**
     * Add a decision.
     */
    public function addDecision(Decision $decision): void
    {
        $this->decisions[] = $decision;
    }

    /**
     * Add multiple decisions.
     *
     * @param Decision[] $decisions
     */
    public function addDecisions(array $decisions): void
    {
        foreach ($decisions as $decision) {
            $this->addDecision($decision);
        }
    }

    /**
     * Get the agenda points.
     *
     * @return Collection<array-key, MeetingPoint>
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    /**
     * Add an agenda point.
     */
    public function addPoint(MeetingPoint $point): void
    {
        $this->points[] = $point;
    }

    /**
     * Get the documents.
     *
     * @return Collection<array-key, MeetingDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * Add a document.
     */
    public function addDocument(MeetingDocument $document): void
    {
        $this->documents[] = $document;
    }

    /**
     * Add multiple documents.
     *
     * @param MeetingDocument[] $documents
     */
    public function addDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
    }

    public function getMinutes(): ?MeetingMinutes
    {
        return $this->meetingMinutes;
    }

    /**
     * Keeps the inverse side in sync, so a change is visible within the same request (a live component re-renders
     * without a fresh entity load).
     */
    public function setMeetingMinutes(?MeetingMinutes $meetingMinutes): void
    {
        $this->meetingMinutes = $meetingMinutes;
    }

    public function getLocalDetails(): ?MeetingLocalDetails
    {
        return $this->localDetails;
    }

    /**
     * Keeps the inverse side in sync, so a change is visible within the same request (a live component re-renders
     * without a fresh entity load).
     */
    public function setLocalDetails(?MeetingLocalDetails $localDetails): void
    {
        $this->localDetails = $localDetails;
    }
}
