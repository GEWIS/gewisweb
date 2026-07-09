<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * When a {@see AllocationMethod::ConditionalDraw} is performed automatically
 * ({@see \App\Command\Activity\RunDueDrawsCommand}); the board can still run it by hand once the moment has passed.
 *
 * - {@see self::IfFullBefore}: draw at a given moment, regardless of fullness. When the list is oversubscribed by then
 *   it is a real lottery; when it is not, everyone so far is admitted and the locked list hands out its remaining
 *   places first-come-first-served.
 * - {@see self::AfterDurationOpen}: draw once it has been open for a number of hours (possibly before it closes).
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
