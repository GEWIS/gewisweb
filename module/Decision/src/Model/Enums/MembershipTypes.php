<?php
// phpcs:ignoreFile

namespace Decision\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for keeping track of the claims that can be present in the JWT for ApiApps.
 */
enum MembershipTypes: string
{
    case Ordinary = 'ordinary';
    case External = 'external';
    case Graduate = 'graduate';
    case Honorary = 'honorary';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Ordinary => $translator->translate('Ordinary'),
            self::External => $translator->translate('External'),
            self::Graduate => $translator->translate('Graduate'),
            self::Honorary => $translator->translate('Honorary'),
        };
    }
}
