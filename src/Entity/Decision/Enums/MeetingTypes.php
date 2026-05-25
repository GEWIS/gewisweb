<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use InvalidArgumentException;
use Symfony\Component\Translation\TranslatableMessage;

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

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::BV => new TranslatableMessage('Board Meeting'),
            self::ALV => new TranslatableMessage('General Members Meeting'),
            self::VV => new TranslatableMessage('Chair\'s Meeting'),
            self::VIRT => new TranslatableMessage('Virtual Meeting'),
        };
    }

    public function abbreviation(): TranslatableMessage
    {
        return match ($this) {
            self::BV => new TranslatableMessage('BM'),
            self::ALV => new TranslatableMessage('GMM'),
            self::VV => new TranslatableMessage('CM'),
            self::VIRT => new TranslatableMessage('VIRT'),
        };
    }

    /**
     * @return string[]
     */
    public static function getSearchableStrings(): array
    {
        return [
            ...array_column(
                self::cases(),
                'value',
            ),
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
