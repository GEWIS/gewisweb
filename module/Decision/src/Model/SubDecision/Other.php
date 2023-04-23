<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\Entity;

/**
 * Entity for undefined decisions.
 */
#[Entity]
class Other extends SubDecision
{
}
