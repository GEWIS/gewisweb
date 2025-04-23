<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use Decision\Model\Meeting;
use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Decisions on minutes.
 */
#[Entity]
class Minutes extends SubDecision
{
    /**
     * Reference to the meetings
     */
    #[OneToOne(
        targetEntity: Meeting::class,
        inversedBy: 'minutes',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'number',
    )]
    protected Meeting $meeting;

    /**
     * If the minutes were approved.
     */
    #[Column(type: 'boolean')]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    protected bool $changes;

    /**
     * Get the target.
     */
    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the target.
     */
    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
    }

    /**
     * Get approval status.
     */
    public function getApproval(): bool
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     */
    public function setApproval(bool $approval): void
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     */
    public function getChanges(): bool
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     */
    public function setChanges(bool $changes): void
    {
        $this->changes = $changes;
    }
}
