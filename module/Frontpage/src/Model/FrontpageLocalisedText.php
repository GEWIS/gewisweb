<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the Frontpage module.
 */
#[Entity]
class FrontpageLocalisedText extends LocalisedText
{
}
