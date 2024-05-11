<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Reference to organ foundation.
 *
 * Note that this should not be directly used. It is in the inheritance map,
 * but that is only to make it usable as mappable entity.
 */
#[Entity]
abstract class FoundationReference extends SubDecision
{
    /**
     * Reference to foundation of organ.
     */
    #[ManyToOne(
        targetEntity: Foundation::class,
        inversedBy: 'references',
        cascade: ['persist'],
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
    protected Foundation $foundation;

    /**
     * Get organ foundation.
     */
    public function getFoundation(): Foundation
    {
        return $this->foundation;
    }

    /**
     * Set organ foundation.
     */
    public function setFoundation(Foundation $foundation): void
    {
        $this->foundation = $foundation;
    }
}
