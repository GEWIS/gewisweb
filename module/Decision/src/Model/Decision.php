<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decision model.
 *
 * @ORM\Entity
 */
class Decision
{
    /**
     * Meeting.
     *
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="decisions")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="meeting_type", referencedColumnName="type"),
     *     @ORM\JoinColumn(name="meeting_number", referencedColumnName="number"),
     * })
     */
    protected $meeting;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $meeting_type;

    /**
     * Meeting number.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $meeting_number;

    /**
     * Point in the meeting in which the decision was made.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $point;

    /**
     * Decision number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $number;

    /**
     * Content.
     *
     * Generated from subdecisions.
     *
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * Subdecisions.
     *
     * @ORM\OneToMany(targetEntity="SubDecision", mappedBy="decision", cascade={"persist", "remove"})
     * @ORM\OrderBy({"number": "ASC"})
     */
    protected $subdecisions;

    /**
     * Destroyed by.
     *
     * @ORM\OneToOne(targetEntity="\Decision\Model\SubDecision\Destroy", mappedBy="target")
     */
    protected $destroyedby;

    /**
     * Set the meeting.
     */
    public function setMeeting(Meeting $meeting)
    {
        $meeting->addDecision($this);
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
        $this->meeting = $meeting;
    }

    /**
     * Get the meeting type.
     *
     * @return string
     */
    public function getMeetingType()
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getMeetingNumber()
    {
        return $this->meeting_number;
    }

    /**
     * Get the meeting.
     *
     * @return Meeting
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * Set the point number.
     *
     * @param int $point
     */
    public function setPoint($point)
    {
        $this->point = $point;
    }

    /**
     * Get the point number.
     *
     * @return int
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set the decision number.
     *
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get the decision number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Get decision content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set decision content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get the subdecisions.
     *
     * @return array
     */
    public function getSubdecisions()
    {
        return $this->subdecisions;
    }

    /**
     * Add a subdecision.
     *
     * @param SubDecision $subdecision
     */
    public function addSubdecision(SubDecision $subdecision)
    {
        $this->subdecisions[] = $subdecision;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param array $subdecisions
     */
    public function addSubdecisions($subdecisions)
    {
        foreach ($subdecisions as $subdecision) {
            $this->addSubdecision($subdecision);
        }
    }

    /**
     * Get the subdecision by which this decision is destroyed.
     *
     * Or null, if it wasn't destroyed.
     *
     * @return SubDecision\Destroy
     */
    public function getDestroyedBy()
    {
        return $this->destroyedby;
    }

    /**
     * Check if this decision is destroyed by another decision.
     *
     * @return bool
     */
    public function isDestroyed()
    {
        return null !== $this->destroyedby;
    }
}
