<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * When a {@see AllocationMethod::ConditionalDraw} should be performed. Captured as configuration on the sign-up list;
 * the draw is still run by the board (automated execution at the cutoff is future work).
 *
 * - {@see self::IfFullBefore}: draw if the list is full before a given moment.
 * - {@see self::AfterDurationOpen}: draw once it has been open for a number of hours.
 * - {@see self::OnClose}: draw when the sign-up list closes.
 */
enum DrawCutoffRule: string implements TranslatableInterface
{
    case IfFullBefore = 'if-full-before';
    case AfterDurationOpen = 'after-duration-open';
    case OnClose = 'on-close';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::IfFullBefore => $translator->trans(
                'Draw if full before a date',
                locale: $locale,
            ),
            self::AfterDurationOpen => $translator->trans(
                'Draw after being open for a while',
                locale: $locale,
            ),
            self::OnClose => $translator->trans(
                'Draw when sign-up closes',
                locale: $locale,
            ),
        };
    }
}
