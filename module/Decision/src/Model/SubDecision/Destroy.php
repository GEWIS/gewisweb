<?php

namespace Decision\Model\SubDecision;

use Decision\Model\{
    Decision,
    SubDecision,
};
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    OneToOne,
};

/**
 * Destroying a decision.
 *
 * This decision references to a different decision. The given decision is
 * destroyed, as if it did never exist.
 *
 * Note that this behaviour might not always work flawlessly. It is very
 * complicated, and thus there might be edge cases that I didn't completely
 * catch. If that is the case, let me know!
 *
 * Also note that destroying decisions that destroy is undefined behaviour!
 *
 * @author Pieter Kokx <kokx@gewis.nl>
 */
#[Entity]
class Destroy extends SubDecision
{
    /**
     * Reference to the destruction of a decision.
     */
    #[OneToOne(
        targetEntity: "Decision\Model\Decision",
        inversedBy: "destroyedby",
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
        referencedColumnName: "point",
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "number",
    )]
    protected Decision $target;

    /**
     * Get the target.
     *
     * @return Decision
     */
    public function getTarget(): Decision
    {
        return $this->target;
    }

    /**
     * Set the target.
     *
     * @param Decision $target
     */
    public function setTarget(Decision $target): void
    {
        $this->target = $target;
    }
}
