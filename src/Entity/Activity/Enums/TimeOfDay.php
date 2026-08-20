<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Which part of the day a proposed activity would take up.
 *
 * A date option books whole days, so this is what says whether the body wants the evening or the entire day. It is
 * what the board reads when two options land on the same date: a lunch break and an evening can both go ahead.
 */
enum TimeOfDay: string implements TranslatableInterface
{
    case Morning = 'morning';
    case LunchBreak = 'lunch-break';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case Day = 'day';
    case MultipleDays = 'multiple-days';

    /**
     * Whether options with this part of the day and another can sit on one date without getting in each other's way.
     * Anything covering a whole day collides with everything.
     */
    public function overlaps(self $other): bool
    {
        if (
            self::Day === $this
            || self::MultipleDays === $this
            || self::Day === $other
            || self::MultipleDays === $other
        ) {
            return true;
        }

        return $this === $other;
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Morning => $translator->trans(
                'Morning',
                locale: $locale,
            ),
            self::LunchBreak => $translator->trans(
                'Lunch break',
                locale: $locale,
            ),
            self::Afternoon => $translator->trans(
                'Afternoon',
                locale: $locale,
            ),
            self::Evening => $translator->trans(
                'Evening',
                locale: $locale,
            ),
            self::Day => $translator->trans(
                'Whole day',
                locale: $locale,
            ),
            self::MultipleDays => $translator->trans(
                'Multiple days',
                locale: $locale,
            ),
        };
    }
}
