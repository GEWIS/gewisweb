<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use DateTimeInterface;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function intdiv;

/**
 * Produces a short, localised "coarse" duration between two moments (largest non-zero unit), e.g. "3 weeks",
 * "2 days", "5 hours". Used by the overview to phrase how long until an activity starts / a sign-up closes, and how
 * long an ongoing activity still runs. The caller wraps it (e.g. "in %duration%", "ongoing for %duration%").
 */
class RelativeTimeExtension extends AbstractExtension
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'human_time_diff',
                $this->humanTimeDiff(...),
            ),
        ];
    }

    public function humanTimeDiff(
        DateTimeInterface $from,
        DateTimeInterface $to,
    ): string {
        $interval = $from->diff($to);
        $days = $interval->days;

        if ($days >= 7) {
            return $this->translator->trans(
                '%count% week|%count% weeks',
                [
                    '%count%' => intdiv(
                        $days,
                        7,
                    ),
                ],
            );
        }

        if ($days >= 1) {
            return $this->translator->trans(
                '%count% day|%count% days',
                ['%count%' => $days],
            );
        }

        if ($interval->h >= 1) {
            return $this->translator->trans(
                '%count% hour|%count% hours',
                ['%count%' => $interval->h],
            );
        }

        if ($interval->i >= 1) {
            return $this->translator->trans(
                '%count% minute|%count% minutes',
                ['%count%' => $interval->i],
            );
        }

        return $this->translator->trans('less than a minute');
    }
}
