<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How the financial side of a scheduled proposal was settled.
 *
 * Association policy is that an organiser may not commit, spend or promote before their budget has been approved at a
 * board meeting, which should be at least four weeks before the activity. Board meetings are not known in advance, so
 * the website cannot work out that deadline itself; the board records the outcome here instead.
 *
 * It is not a flag, because an activity that costs nothing has no budget to hand in and must not be chased for one.
 */
enum BudgetClearance: string implements TranslatableInterface
{
    /** A budget was handed in and approved at a board meeting. */
    case Approved = 'approved';

    /** The activity costs nothing, so there is no budget to approve. */
    case NotRequired = 'not-required';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Approved => $translator->trans(
                'Budget approved',
                locale: $locale,
            ),
            self::NotRequired => $translator->trans(
                'No budget needed',
                locale: $locale,
            ),
        };
    }
}
