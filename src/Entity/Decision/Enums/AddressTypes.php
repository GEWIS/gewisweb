<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Enum for the different address types.
 */
enum AddressTypes: string
{
    case Home = 'home';
    case Student = 'student';
    case Mail = 'mail';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Home => new TranslatableMessage('Home address (parents)'),
            self::Student => new TranslatableMessage('Student address'),
            self::Mail => new TranslatableMessage('Mail address'),
        };
    }
}
