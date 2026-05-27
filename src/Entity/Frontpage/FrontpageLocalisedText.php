<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the Frontpage module.
 */
#[Entity]
class FrontpageLocalisedText extends LocalisedText
{
}
