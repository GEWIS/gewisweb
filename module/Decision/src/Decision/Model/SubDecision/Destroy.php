<?php

namespace Decision\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;
use Decision\Model\SubDecision;
use Decision\Model\Decision;

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
 *
 * @ORM\Entity
 */
class Destroy extends SubDecision
{
    /**
     * Reference to the destruction of a decision.
     *
     * @ORM\OneToOne(targetEntity="\Decision\Model\Decision",inversedBy="destroyedby")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="number"),
     * })
     */
    protected $target;

    /**
     * Get the target.
     *
     * @return SubDecision
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set the target.
     *
     * @param Target $target
     */
    public function setTarget(Decision $target)
    {
        $this->target = $target;
    }
}
