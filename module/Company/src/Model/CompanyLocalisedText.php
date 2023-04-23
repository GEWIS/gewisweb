<?php

declare(strict_types=1);

namespace Company\Model;

use Application\Model\LocalisedText;
use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the Company module.
 */
#[Entity]
class CompanyLocalisedText extends LocalisedText
{
}
