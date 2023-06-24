<?php

declare(strict_types=1);

namespace Decision\Model;

use DateTime;
use Decision\Model\Enums\MeetingTypes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

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
        type: 'string',
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $number;

    /**
     * Meeting date.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Decisions.
     *
     * @var Collection<array-key, Decision>
     */
    #[OneToMany(
        targetEntity: Decision::class,
        mappedBy: 'meeting',
    )]
    protected Collection $decisions;

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
    protected Collection $documents;

    /**
     * The minutes for this meeting.
     */
    #[OneToOne(
        targetEntity: MeetingMinutes::class,
        mappedBy: 'meeting',
    )]
    protected ?MeetingMinutes $meetingMinutes = null;

    public function __construct()
    {
        $this->decisions = new ArrayCollection();
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

    public function getMinutes(): ?MeetingMinutes
    {
        return $this->meetingMinutes;
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
}
