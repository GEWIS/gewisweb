<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Which rule decided how many activities a body may propose in a period.
 *
 * Resolving a limit is a ladder from the most specific rule to the least, and this says which rung answered. It is
 * carried alongside the number so a body is told why it may propose what it may, rather than being handed a bare
 * figure it cannot argue with.
 */
enum ProposalLimitSource: string implements TranslatableInterface
{
    /** The board set a limit for this body in this period alone. */
    case PeriodOverride = 'period-override';

    /** The board set a limit for this body that stands until they change it. */
    case StandingOverride = 'standing-override';

    /** The board set a different default for everybody in this period. */
    case PeriodDefault = 'period-default';

    /** Nobody set anything, so the association-wide default applies. */
    case GlobalDefault = 'global-default';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::PeriodOverride => $translator->trans(
                'a limit the board set for your body in this period',
                locale: $locale,
            ),
            self::StandingOverride => $translator->trans(
                'a limit the board set for your body',
                locale: $locale,
            ),
            self::PeriodDefault => $translator->trans(
                'the number the board set for this period',
                locale: $locale,
            ),
            self::GlobalDefault => $translator->trans(
                'the number every body gets',
                locale: $locale,
            ),
        };
    }
}
