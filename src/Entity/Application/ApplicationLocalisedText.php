<?php

declare(strict_types=1);

namespace App\Entity\Application;

use Doctrine\ORM\Mapping\Entity;

/**
 * {@link LocalisedText} for the application module (notifications and announcements).
 */
#[Entity]
class ApplicationLocalisedText extends LocalisedText
{
}
