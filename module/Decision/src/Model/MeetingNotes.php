<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    OneToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Meeting notes.
 */
#[Entity]
class MeetingNotes implements ResourceInterface
{
    /**
     * Meeting type.
     */
    #[Id]
    #[Column(type: "string")]
    protected string $type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $number;

    /**
     * The corresponding meeting for these notes.
     */
    #[OneToOne(
        targetEntity: "Decision\Model\Meeting",
        inversedBy: "meetingNotes",
    )]
    #[JoinColumn(
        name: "type",
        referencedColumnName: "type",
        nullable: false,
    )]
    #[JoinColumn(
        name: "number",
        referencedColumnName: "number",
        nullable: false,
    )]
    protected Meeting $meeting;

    /**
     * The storage path.
     */
    #[Column(type: "string")]
    protected string $path;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param Meeting $meeting
     */
    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
        $this->type = $meeting->getType();
        $this->number = $meeting->getNumber();
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'meeting_notes';
    }
}
