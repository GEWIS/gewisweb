<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision\Board;

use DateTime;
use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Release from board duties.
 *
 * This decision references to an installation. The duties of this installation
 * are released by this release.
 */
#[Entity]
class Release extends SubDecision
{
    /**
     * Reference to the installation of a member.
     */
    #[OneToOne(
        targetEntity: Installation::class,
        inversedBy: 'release',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_sequence',
        referencedColumnName: 'sequence',
    )]
    protected Installation $installation;

    /**
     * Date of the discharge.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Get installation.
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
    }

    /**
     * Get the date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }
}
