<?php

namespace Decision\Model\SubDecision;

use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping as ORM;

/**
 * Reference to organ foundation.
 *
 * Note that this should not be directly used. It is in the inheritance map,
 * but that is only to make it usable as mappable entity.
 *
 * @ORM\Entity
 */
abstract class FoundationReference extends SubDecision
{
    /**
     * Reference to foundation of organ.
     *
     * @ORM\ManyToOne(targetEntity="Foundation", inversedBy="references", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *     @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *     @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *     @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *     @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $foundation;

    /**
     * Get organ foundation.
     *
     * @return Foundation
     */
    public function getFoundation()
    {
        return $this->foundation;
    }

    /**
     * Set organ foundation.
     */
    public function setFoundation(Foundation $foundation)
    {
        $this->foundation = $foundation;
    }
}
