<?php

declare(strict_types=1);

namespace App\Entity\Decision\SubDecision;

use App\Entity\Decision\SubDecision;
use Doctrine\ORM\Mapping\Entity;

/**
 * Entity for undefined decisions.
 */
#[Entity]
class Other extends SubDecision
{
}
