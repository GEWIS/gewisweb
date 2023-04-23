<?php

declare(strict_types=1);

namespace Decision\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different address types.
 */
enum MeetingTypes: string
{
    case BV = 'BV'; // bestuursvergadering
    case ALV = 'ALV'; // algemene leden vergadering
    case VV = 'VV'; // voorzitters vergadering
    case VIRT = 'Virt'; // virtual meeting

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::BV => $translator->translate('Board Meeting'),
            self::ALV => $translator->translate('General Members Meeting'),
            self::VV => $translator->translate('Chair\'s Meeting'),
            self::VIRT => $translator->translate('Virtual Meeting'),
        };
    }

    public function getAbbreviation(Translator $translator): string
    {
        return match ($this) {
            self::BV => $translator->translate('BM'),
            self::ALV => $translator->translate('GMM'),
            self::VV => $translator->translate('CM'),
            self::VIRT => $translator->translate('VIRT'),
        };
    }
}
