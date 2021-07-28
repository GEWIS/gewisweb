<?php

namespace Decision\Model\SubDecision\Board;

use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    OneToOne,
};

/**
 * Discharge from board position.
 *
 * This decision references to an installation. The given installation is
 * 'undone' by this discharge.
 */
#[Entity]
class Discharge extends SubDecision
{
    /**
     * Reference to the installation of a member.
     */
    #[OneToOne(
        targetEntity: "Decision\Model\SubDecision\Board\Installation",
        inversedBy: "discharge",
    )]
    #[JoinColumn(
        name: "r_meeting_type",
        referencedColumnName: "meeting_type",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_meeting_number",
        referencedColumnName: "meeting_number",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_decision_point",
        referencedColumnName: "decision_point",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "decision_number",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_number",
        referencedColumnName: "number",
        nullable: false,
    )]
    protected Installation $installation;

    /**
     * Get installation.
     *
     * @return Installation
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     *
     * @param Installation $installation
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
    }
}
