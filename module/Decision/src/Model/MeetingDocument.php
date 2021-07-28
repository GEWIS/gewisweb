<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * Meeting document model.
 */
#[Entity]
class MeetingDocument
{
    /**
     * Document id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Meeting.
     */
    #[ManyToOne(
        targetEntity: Meeting::class,
        inversedBy: "documents",
    )]
    #[JoinColumn(
        name: "meeting_type",
        referencedColumnName: "type",
    )]
    #[JoinColumn(
        name: "meeting_number",
        referencedColumnName: "number",
    )]
    protected Meeting $meeting;

    /**
     * Name of the document.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Path of the document, relative to the storage directory.
     */
    #[Column(type: "string")]
    protected string $path;

    /**
     * Determines the order in which to display the document.
     *
     * The order is determined by sorting the positions in ascending order.
     */
    #[Column(
        type: "integer",
        options: ["default" => 0],
    )]
    protected int $displayPosition;

    /**
     * Get the document id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the meeting.
     *
     * @return Meeting
     */
    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the meeting.
     *
     * @param Meeting $meeting
     */
    public function setMeeting(Meeting $meeting): void
    {
        $meeting->addDocument($this);
        $this->meeting = $meeting;
    }

    /**
     * Get the name of the document.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the document.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the path.
     *
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return int
     */
    public function getDisplayPosition(): int
    {
        return $this->displayPosition;
    }

    /**
     * @param int $position
     */
    public function setDisplayPosition(int $position): void
    {
        $this->displayPosition = $position;
    }
}
