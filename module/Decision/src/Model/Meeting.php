<?php

namespace Decision\Model;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    OneToMany,
    OneToOne,
    OrderBy,
};
use InvalidArgumentException;

/**
 * Meeting model.
 */
#[Entity]
class Meeting
{
    public const TYPE_BV = 'BV'; // bestuursvergadering
    public const TYPE_AV = 'AV'; // algemene leden vergadering
    public const TYPE_VV = 'VV'; // voorzitters vergadering
    public const TYPE_VIRT = 'Virt'; // virtual meeting

    /**
     * Meeting type.
     */
    #[Id]
    #[Column(type: "string")]
    protected $type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected $number;

    /**
     * Meeting date.
     */
    #[Column(type: "date")]
    protected $date;

    /**
     * Decisions.
     */
    #[OneToMany(
        targetEntity: "Decision\Model\Decision",
        mappedBy: "meeting",
    )]
    protected ArrayCollection $decisions;

    /**
     * Documents.
     */
    #[OneToMany(
        targetEntity: "Decision\Model\MeetingDocument",
        mappedBy: "meeting",
    )]
    #[OrderBy(value: ["displayPosition" => "ASC"])]
    protected ArrayCollection $documents;

    /**
     * The notes for this meeting.
     */
    #[OneToOne(
        targetEntity: "Decision\Model\MeetingNotes",
        mappedBy: "meeting",
    )]
    protected MeetingNotes $meetingNotes;

    /**
     * Get all allowed meeting types.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BV,
            self::TYPE_AV,
            self::TYPE_VV,
            self::TYPE_VIRT,
        ];
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->decisions = new ArrayCollection();
    }

    /**
     * Get the meeting type.
     *
     * @return string
     */
    public function getType(): string
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
     * @return MeetingNotes
     */
    public function getNotes(): MeetingNotes
    {
        return $this->meetingNotes;
    }

    /**
     * Set the meeting type.
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        if (!in_array($type, self::getTypes())) {
            throw new InvalidArgumentException('Invalid meeting type given.');
        }
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
     * @return ArrayCollection
     */
    public function getDecisions(): ArrayCollection
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
     * @return ArrayCollection
     */
    public function getDocuments(): ArrayCollection
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
