<?php

declare(strict_types=1);

namespace Decision\Model\Enums;

use InvalidArgumentException;
use Laminas\Mvc\I18n\Translator;

use function array_column;

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

    /**
     * @return string[]
     */
    public static function getSearchableStrings(): array
    {
        return [
            ...array_column(self::cases(), 'value'),
            'GMM',
            'BM',
            'CM',
        ];
    }

    public static function tryFromSearch(string $input): MeetingTypes
    {
        $value = self::tryFrom($input);

        if (null !== $value) {
            return $value;
        }

        return match ($input) {
            'GMM' => MeetingTypes::ALV,
            'BM' => MeetingTypes::BV,
            'CM' => MeetingTypes::VV,
            default => throw new InvalidArgumentException('MeetingType is not recognized'),
        };
    }
}
