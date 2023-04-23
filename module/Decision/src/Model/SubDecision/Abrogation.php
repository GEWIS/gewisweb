<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use Doctrine\ORM\Mapping\Entity;

/**
 * Abrogation of an organ.
 */
#[Entity]
class Abrogation extends FoundationReference
{
}
