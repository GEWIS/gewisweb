<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * How the token is handed back to an external application. Legacy applications receive it as a query parameter; modern
 * applications require the URL fragment, since those are not logged or cached the way query strings can be.
 */
enum ExternalAppTokenDelivery: string
{
    case Query = 'query';
    case Fragment = 'fragment';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Query => new TranslatableMessage('Query parameter (?token=)'),
            self::Fragment => new TranslatableMessage('URL fragment (#token=)'),
        };
    }

    public function separator(): string
    {
        return match ($this) {
            self::Query => '?token=',
            self::Fragment => '#token=',
        };
    }
}
