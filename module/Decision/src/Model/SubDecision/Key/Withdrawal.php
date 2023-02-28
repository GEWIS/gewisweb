<?php

namespace Decision\Model\SubDecision\Key;

use DateTime;
use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    OneToOne,
};

#[Entity]
class Withdrawal extends SubDecision
{
    /**
     * Reference to the granting of a keycode.
     */
    #[OneToOne(
        targetEntity: Granting::class,
        inversedBy: "withdrawal",
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
    protected Granting $granting;

    /**
     * When the granted keycode is prematurely revoked.
     */
    #[Column(type: "date")]
    protected DateTime $withdrawnOn;

    /**
     * Get the granting of the keycode.
     *
     * @return Granting
     */
    public function getGranting(): Granting
    {
        return $this->granting;
    }

    /**
     * Set the granting of the keycode.
     */
    public function setGranting(Granting $granting): void
    {
        $this->granting = $granting;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getWithdrawnOn(): DateTime
    {
        return $this->withdrawnOn;
    }

    /**
     * Set the date.
     *
     * @param DateTime $withdrawnOn
     */
    public function setWithdrawnOn(DateTime $withdrawnOn): void
    {
        $this->withdrawnOn = $withdrawnOn;
    }
}
