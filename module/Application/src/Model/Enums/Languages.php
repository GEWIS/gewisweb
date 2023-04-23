<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * The different languages supported by the website.
 */
enum Languages: string
{
    case EN = 'en';
    case NL = 'nl';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::EN => $translator->translate('English'),
            self::NL => $translator->translate('Dutch'),
        };
    }

    public static function values(): array
    {
        return array_merge(
            array_map(
                fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
