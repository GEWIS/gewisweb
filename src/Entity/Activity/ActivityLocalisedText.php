<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the Activity module.
 */
#[Entity]
class ActivityLocalisedText extends LocalisedText
{
}
