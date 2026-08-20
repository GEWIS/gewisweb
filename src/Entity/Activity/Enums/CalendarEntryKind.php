<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * What a block on the option calendar stands for.
 *
 * Four things share the grid and must not be mistaken for one another: an activity the website itself holds, an item
 * from the association's own agenda that it does not, a day a body has been given, and a day a body is still asking
 * for. The paper calendar made much the same distinction with two colours of pen.
 */
enum CalendarEntryKind: string implements TranslatableInterface
{
    case FixedActivity = 'fixed';
    case AgendaItem = 'agenda';
    case ReservedDay = 'reserved';
    case RequestedDay = 'requested';

    /**
     * The modifier its block is drawn with, so the same thing looks the same wherever the calendar is shown.
     */
    public function modifier(): string
    {
        return 'option-calendar__entry--' . $this->value;
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::FixedActivity => $translator->trans(
                'Already in the agenda',
                locale: $locale,
            ),
            self::AgendaItem => $translator->trans(
                'Elsewhere in the agenda',
                locale: $locale,
            ),
            self::ReservedDay => $translator->trans(
                'Day reserved',
                locale: $locale,
            ),
            self::RequestedDay => $translator->trans(
                'Day asked for',
                locale: $locale,
            ),
        };
    }
}
