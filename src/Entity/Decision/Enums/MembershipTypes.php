<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Enum for the different membership types as defined in the articles of association.
 */
enum MembershipTypes: string
{
    case Ordinary = 'ordinary';
    case External = 'external';
    case Graduate = 'graduate';
    case Honorary = 'honorary';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Ordinary => new TranslatableMessage('Ordinary'),
            self::External => new TranslatableMessage('External'),
            self::Graduate => new TranslatableMessage('Graduate'),
            self::Honorary => new TranslatableMessage('Honorary'),
        };
    }
}
