<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\IdentifiableTrait;
use Application\Model\Traits\TimestampableTrait;
use DateTime;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PreUpdate;

/**
 * Meeting document model.
 */
#[Entity]
#[HasLifecycleCallbacks]
class MeetingDocument
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * Meeting.
     */
    #[ManyToOne(
        targetEntity: Meeting::class,
        inversedBy: 'documents',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
    )]
    protected Meeting $meeting;

    /**
     * Name of the document.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Path of the document, relative to the storage directory.
     */
    #[Column(type: 'string')]
    protected string $path;

    /**
     * Determines the order in which to display the document.
     *
     * The order is determined by sorting the positions in ascending order.
     */
    #[Column(
        type: 'integer',
        options: ['default' => 0],
    )]
    protected int $displayPosition;

    /**
     * Get the meeting.
     */
    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the meeting.
     */
    public function setMeeting(Meeting $meeting): void
    {
        $meeting->addDocument($this);
        $this->meeting = $meeting;
    }

    /**
     * Get the name of the document.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the document.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the path.
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getDisplayPosition(): int
    {
        return $this->displayPosition;
    }

    public function setDisplayPosition(int $position): void
    {
        $this->displayPosition = $position;
    }

    /**
     * Override the `preUpdate` lifecycle callback to prevent updating the timestamp when changing the display position.
     */
    #[PreUpdate]
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField('displayPosition')) {
            return;
        }

        $this->setUpdatedAt(new DateTime());
    }
}
