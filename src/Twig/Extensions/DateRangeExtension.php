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
 * Locale-aware begin/end range formatting shared across the application:
 *  - single day:    "Saturday 4 July" (no time) or "Thursday 3 Sep. 12:40 - 13:20" (with time)
 *  - multiple days: "Thu. 3 Sep. - Sat. 5 Sep." (+ " (HH:mm)" per side with time)
 *
 * `date_range()` is the general helper (opt into times and years); `activity_date_range()` is the activity preset
 * (always with times, year shown when it differs from today) and `activity_date_badge()` splits the parts for the
 * stacked overview badge.
 */
class DateRangeExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'date_range',
                $this->dateRange(...),
            ),
            new TwigFunction(
                'activity_date_range',
                $this->activityDateRange(...),
            ),
            new TwigFunction(
                'activity_date_badge',
                $this->activityDateBadge(...),
            ),
        ];
    }

    /**
     * @param string $year 'auto' shows a side's year only when it differs from the current year; 'never' omits it.
     */
    public function dateRange(
        DateTimeInterface $begin,
        DateTimeInterface $end,
        bool $time = true,
        string $year = 'auto',
    ): string {
        $locale = Locale::getDefault();

        if ($begin->format('Y-m-d') === $end->format('Y-m-d')) {
            $pattern = $time
                ? 'EEEE d MMM.'
                : 'EEEE d MMMM';
            if (
                $this->showYear(
                    $begin,
                    $year,
                )
            ) {
                $pattern .= ' yyyy';
            }

            if ($time) {
                $pattern .= ' HH:mm';
            }

            $rendered = $this->format(
                $begin,
                $pattern,
                $locale,
            );
            if ($time) {
                $rendered = sprintf(
                    '%s - %s',
                    $rendered,
                    $this->format(
                        $end,
                        'HH:mm',
                        $locale,
                    ),
                );
            }

            return ucfirst($rendered);
        }

        return ucfirst(sprintf(
            '%s - %s',
            $this->format(
                $begin,
                $this->rangeSidePattern(
                    $begin,
                    $time,
                    $year,
                ),
                $locale,
            ),
            $this->format(
                $end,
                $this->rangeSidePattern(
                    $end,
                    $time,
                    $year,
                ),
                $locale,
            ),
        ));
    }

    public function activityDateRange(
        DateTimeInterface $begin,
        DateTimeInterface $end,
    ): string {
        return $this->dateRange(
            $begin,
            $end,
            true,
            'auto',
        );
    }

    /**
     * Locale-aware split date parts for the left-hand stacked badge on the activity overview. Uppercasing is left to
     * CSS (text-transform) to stay locale-correct for weekday/month abbreviations. The year is deliberately omitted to
     * keep the badge compact; activity_date_range() beside the title already shows the year when it differs from today.
     *
     * @return array{weekday: string, day: string, month: string, range: bool}
     */
    public function activityDateBadge(
        DateTimeInterface $begin,
        DateTimeInterface $end,
    ): array {
        $locale = Locale::getDefault();
        $sameDay = $begin->format('Y-m-d') === $end->format('Y-m-d');
        $sameMonth = $begin->format('Y-m') === $end->format('Y-m');

        if ($sameDay) {
            return [
                'weekday' => $this->format(
                    $begin,
                    'EEE',
                    $locale,
                ),
                'day' => $this->format(
                    $begin,
                    'd',
                    $locale,
                ),
                'month' => $this->format(
                    $begin,
                    'MMM',
                    $locale,
                ),
                'range' => false,
            ];
        }

        return [
            'weekday' => sprintf(
                '%s - %s',
                $this->format(
                    $begin,
                    'EEE',
                    $locale,
                ),
                $this->format(
                    $end,
                    'EEE',
                    $locale,
                ),
            ),
            'day' => sprintf(
                '%s - %s',
                $this->format(
                    $begin,
                    'dd',
                    $locale,
                ),
                $this->format(
                    $end,
                    'dd',
                    $locale,
                ),
            ),
            'month' => $sameMonth
                ? $this->format(
                    $begin,
                    'MMM',
                    $locale,
                )
                : sprintf(
                    '%s - %s',
                    $this->format(
                        $begin,
                        'MMM',
                        $locale,
                    ),
                    $this->format(
                        $end,
                        'MMM',
                        $locale,
                    ),
                ),
            'range' => true,
        ];
    }

    private function rangeSidePattern(
        DateTimeInterface $date,
        bool $time,
        string $year,
    ): string {
        $pattern = 'EEE. d MMM.';
        if (
            $this->showYear(
                $date,
                $year,
            )
        ) {
            $pattern .= ' yyyy';
        }

        if ($time) {
            $pattern .= ' (HH:mm)';
        }

        return $pattern;
    }

    private function showYear(
        DateTimeInterface $date,
        string $year,
    ): bool {
        return match ($year) {
            'always' => true,
            'never' => false,
            default => $date->format('Y') !== new DateTime()->format('Y'),
        };
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
