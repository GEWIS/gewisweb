<?php

declare(strict_types=1);

namespace Decision\Model;

use DateTime;
use Decision\Model\Enums\MeetingTypes;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    OneToMany,
    OneToOne,
    OrderBy,
};

/**
 * Meeting model.
 */
#[Entity]
class Meeting
{
    /**
     * Meeting type.
     */
    #[Id]
    #[Column(
        type: "string",
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $number;

    /**
     * Meeting date.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * Decisions.
     */
    #[OneToMany(
        targetEntity: Decision::class,
        mappedBy: "meeting",
    )]
    protected Collection $decisions;

    /**
     * Documents.
     */
    #[OneToMany(
        targetEntity: MeetingDocument::class,
        mappedBy: "meeting",
    )]
    #[OrderBy(value: ["displayPosition" => "ASC"])]
    protected Collection $documents;

    /**
     * The minutes for this meeting.
     */
    #[OneToOne(
        targetEntity: MeetingMinutes::class,
        mappedBy: "meeting",
    )]
    protected ?MeetingMinutes $meetingMinutes = null;

    public function __construct()
    {
        $this->decisions = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * Get the meeting type.
     *
     * @return MeetingTypes
     */
    public function getType(): MeetingTypes
    {
        return $this->type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return MeetingMinutes|null
     */
    public function getMinutes(): ?MeetingMinutes
    {
        return $this->meetingMinutes;
    }

    /**
     * Set the meeting type.
     *
     * @param MeetingTypes $type
     */
    public function setType(MeetingTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Set the meeting number.
     *
     * @param int $number
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * Get the meeting date.
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the meeting date.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the decisions.
     *
     * @return Collection
     */
    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    /**
     * Add a decision.
     *
     * @param Decision $decision
     */
    public function addDecision(Decision $decision): void
    {
        $this->decisions[] = $decision;
    }

    /**
     * Add multiple decisions.
     *
     * @param array $decisions
     */
    public function addDecisions(array $decisions): void
    {
        foreach ($decisions as $decision) {
            $this->addDecision($decision);
        }
    }

    /**
     * Get the documents.
     *
     * @return Collection
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * Add a document.
     *
     * @param MeetingDocument $document
     */
    public function addDocument(MeetingDocument $document): void
    {
        $this->documents[] = $document;
    }

    /**
     * Add multiple documents.
     *
     * @param array $documents
     */
    public function addDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
    }
}
