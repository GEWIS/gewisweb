<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Meeting model.
 *
 * @ORM\Entity
 */
class Meeting
{
    const TYPE_BV = 'BV'; // bestuursvergadering
    const TYPE_AV = 'AV'; // algemene leden vergadering
    const TYPE_VV = 'VV'; // voorzitters vergadering
    const TYPE_VIRT = 'Virt'; // virtual meeting

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
     */
    protected $documents;

    /**
     * The notes for this meeting
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
            self::TYPE_VIRT
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
            throw new \InvalidArgumentException("Invalid meeting type given.");
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
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the meeting date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Get the decisions.
     *
     * @return array
     */
    public function getDecisions()
    {
        return $this->decisions;
    }

    /**
     * Add a decision.
     *
     * @param Decision $decision
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
     * @param Document $document
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

    /**
     * Sorts document list
     */
    public function sortDocuments()
    {
        $temp = [];
        foreach ($this->documents as $document) {
            $temp[] = $document;
        }
        usort($temp, function ($a, $b) {
            $aa = preg_split("/(\.|\s)/", $a->getName());
            $bb = preg_split("/(\.|\s)/", $b->getName());
            for ($i = 0; $i < min(count($aa), count($bb)); $i++) {
                if (!is_numeric($aa[$i])) {
                    return -1;
                } elseif (!is_numeric($bb[$i])) {
                    return 1;
                } elseif ($aa[$i] != $bb[$i]) {
                    return $aa[$i] < $bb[$i] ? -1 : 1;
                }
            }
            return 0;
        });
        $this->documents = $temp;
    }

}
