<?php

namespace Decision\Model;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use InvalidArgumentException;

/**
 * Meeting model.
 *
 * @ORM\Entity
 */
class Meeting
{
    public const TYPE_BV = 'BV'; // bestuursvergadering
    public const TYPE_AV = 'AV'; // algemene leden vergadering
    public const TYPE_VV = 'VV'; // voorzitters vergadering
    public const TYPE_VIRT = 'Virt'; // virtual meeting

    /**
     * Meeting type.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Meeting number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $number;

    /**
     * Meeting date.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Decisions.
     *
     * @ORM\OneToMany(targetEntity="Decision", mappedBy="meeting")
     */
    protected $decisions;

    /**
     * Documents.
     *
     * @ORM\OneToMany(targetEntity="MeetingDocument", mappedBy="meeting")
     * @OrderBy({"displayPosition": "ASC"})
     */
    protected $documents;

    /**
     * The notes for this meeting.
     *
     * @ORM\OneToOne(targetEntity="MeetingNotes", mappedBy="meeting")
     */
    protected $meetingNotes;

    /**
     * Get all allowed meeting types.
     */
    public static function getTypes()
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    public function getNotes()
    {
        return $this->meetingNotes;
    }

    /**
     * Set the meeting type.
     *
     * @param string $type
     */
    public function setType($type)
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
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get the meeting date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the meeting date.
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Get the decisions.
     *
     * @return ArrayCollection
     */
    public function getDecisions()
    {
        return $this->decisions;
    }

    /**
     * Add a decision.
     */
    public function addDecision(Decision $decision)
    {
        $this->decisions[] = $decision;
    }

    /**
     * Add multiple decisions.
     *
     * @param array $decisions
     */
    public function addDecisions($decisions)
    {
        foreach ($decisions as $decision) {
            $this->addDecision($decision);
        }
    }

    /**
     * Get the documents.
     *
     * @return array
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Add a document.
     *
     * @param MeetingDocument $document
     */
    public function addDocument(MeetingDocument $document)
    {
        $this->documents[] = $document;
    }

    /**
     * Add multiple documents.
     *
     * @param array $documents
     */
    public function addDocuments($documents)
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
    }
}
