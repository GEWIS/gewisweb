<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use DateTime;
use DateTimeInterface;
use IntlDateFormatter;
use Locale;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function sprintf;
use function ucfirst;

/**
 * Formats an activity's begin/end as a single locale-aware range string, mirroring the previous GEWIS overview:
 *  - single day:   "Thursday 28 May. 12:40 - 13:20"
 *  - multiple days: "Sun. 17 May. (00:00) - Sun. 21 Jun. (23:59)"
 * A side that falls in a different calendar year than today also shows its year (e.g. "… 14 Dec. 2026 …").
 */
class ActivityDateRangeExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'activity_date_range',
                $this->activityDateRange(...),
            ),
        ];
    }

    public function activityDateRange(
        DateTimeInterface $begin,
        DateTimeInterface $end,
    ): string {
        $locale = Locale::getDefault();
        $currentYear = new DateTime()->format('Y');
        $sameDay = $begin->format('Y-m-d') === $end->format('Y-m-d');

        if ($sameDay) {
            $beginPattern = $begin->format('Y') === $currentYear
                ? 'EEEE d MMM. HH:mm'
                : 'EEEE d MMM. yyyy HH:mm';

            $rendered = sprintf(
                '%s - %s',
                $this->format(
                    $begin,
                    $beginPattern,
                    $locale,
                ),
                $this->format(
                    $end,
                    'HH:mm',
                    $locale,
                ),
            );
        } else {
            $beginPattern = $begin->format('Y') === $currentYear
                ? 'EEE. d MMM. (HH:mm)'
                : 'EEE. d MMM. yyyy (HH:mm)';
            $endPattern = $end->format('Y') === $currentYear
                ? 'EEE. d MMM. (HH:mm)'
                : 'EEE. d MMM. yyyy (HH:mm)';

            $rendered = sprintf(
                '%s - %s',
                $this->format(
                    $begin,
                    $beginPattern,
                    $locale,
                ),
                $this->format(
                    $end,
                    $endPattern,
                    $locale,
                ),
            );
        }

        return ucfirst($rendered);
    }

    private function format(
        DateTimeInterface $date,
        string $pattern,
        string $locale,
    ): string {
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $date->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $pattern,
        );

        $result = $formatter->format($date);

        return false === $result
            ? ''
            : $result;
    }
}
