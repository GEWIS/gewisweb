<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Meeting document model.
 *
 * @ORM\Entity
 */
class MeetingDocument
{
    /**
     * Document id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Meeting.
     *
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="documents")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="meeting_type", referencedColumnName="type"),
     *     @ORM\JoinColumn(name="meeting_number", referencedColumnName="number")
     * })
     */
    protected $meeting;

    /**
     * Name of the document.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Path of the document, relative to the storage directory.
     *
     * @ORM\Column(type="string")
     */
    protected $path;

    /**
     * Determines the order in which to display the document.
     *
     * The order is determined by sorting the positions in ascending order.
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    protected $displayPosition;

    /**
     * Get the document id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * Set the meeting.
     */
    public function setMeeting(Meeting $meeting)
    {
        $meeting->addDocument($this);
        $this->meeting = $meeting;
    }

    /**
     * Get the name of the document.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the document.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getDisplayPosition()
    {
        return $this->displayPosition;
    }

    public function setDisplayPosition($position)
    {
        $this->displayPosition = $position;
    }
}
