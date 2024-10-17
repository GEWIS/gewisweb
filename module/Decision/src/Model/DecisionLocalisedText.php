<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the Decision module.
 */
#[Entity]
class DecisionLocalisedText extends LocalisedText
{
}
