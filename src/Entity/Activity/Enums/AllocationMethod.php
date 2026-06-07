<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How the limited places on a {@see \App\Entity\Activity\SignupList} are allocated among its subscribers.
 *
 * - {@see self::FirstComeFirstServed}: admit in sign-up order up to capacity.
 * - {@see self::ConditionalDraw}: a board-run lottery, timed by a {@see DrawCutoffRule}.
 * - {@see self::ExternalParty}: an outside organisation decides; admission is recorded manually.
 * - {@see self::Custom}: a free-form method described by the organiser; admission is recorded manually.
 */
enum AllocationMethod: string implements TranslatableInterface
{
    case FirstComeFirstServed = 'first-come-first-served';
    case ConditionalDraw = 'conditional-draw';
    case ExternalParty = 'external-party';
    case Custom = 'custom';

    /**
     * Whether admission is decided outside this system (an external party or a free-form manual process), so we offer
     * no in-app draw and only record how it is handled.
     */
    public function isManual(): bool
    {
        return match ($this) {
            self::ExternalParty, self::Custom => true,
            self::FirstComeFirstServed, self::ConditionalDraw => false,
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::FirstComeFirstServed => $translator->trans(
                'First-come-first-served',
                locale: $locale,
            ),
            self::ConditionalDraw => $translator->trans(
                'Conditional draw',
                locale: $locale,
            ),
            self::ExternalParty => $translator->trans(
                'External party',
                locale: $locale,
            ),
            self::Custom => $translator->trans(
                'Custom',
                locale: $locale,
            ),
        };
    }
}
