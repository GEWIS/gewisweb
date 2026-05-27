<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the Company module.
 */
#[Entity]
class CareerLocalisedText extends LocalisedText
{
}
