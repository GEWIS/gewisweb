<?php

namespace Decision\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different address types.
 */
enum AddressTypes: string
{
    case Home = 'home';
    case Student = 'student';
    case Mail = 'mail';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Home => $translator->translate('Home address (parents)'),
            self::Student => $translator->translate('Student address'),
            self::Mail => $translator->translate('Mail address'),
        };
    }
}
