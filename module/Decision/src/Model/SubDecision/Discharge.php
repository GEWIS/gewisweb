<?php

namespace Decision\Model\SubDecision;

use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    OneToOne,
};

/**
 * Discharge from organ.
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
        targetEntity: Installation::class,
        inversedBy: "discharge",
    )]
    #[JoinColumn(
        name: "r_meeting_type",
        referencedColumnName: "meeting_type",
    )]
    #[JoinColumn(
        name: "r_meeting_number",
        referencedColumnName: "meeting_number",
    )]
    #[JoinColumn(
        name: "r_decision_point",
        referencedColumnName: "decision_point",
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "decision_number",
    )]
    #[JoinColumn(
        name: "r_number",
        referencedColumnName: "number",
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
