<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * A Dutch and an English text belonging to the decision module.
 */
#[Entity]
class DecisionLocalisedText extends LocalisedText
{
}
