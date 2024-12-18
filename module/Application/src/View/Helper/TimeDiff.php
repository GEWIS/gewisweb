<?php

declare(strict_types=1);

namespace Application\View\Helper;

use DateTime;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Helper\AbstractHelper;

use function array_pop;
use function array_slice;
use function count;
use function floor;
use function implode;
use function sprintf;

class TimeDiff extends AbstractHelper
{
    public function __construct(private readonly Translator $translator)
    {
    }

    public function __invoke(
        DateTime $start,
        DateTime $end,
    ): string {
        $diffInterval = $start->diff($end);
        $diff = [
            'year' => $diffInterval->y,
            'month' => $diffInterval->m,
            'week' => (int) floor($diffInterval->d / 7),
            'day' => $diffInterval->d % 7,
            'hour' => $diffInterval->h,
            'minute' => $diffInterval->i,
            'second' => $diffInterval->s,
        ];

        $units = [];
        $result = [];
        if (
            0 === $diff['year']
            && 0 === $diff['month']
            && 0 === $diff['week']
            && 0 === $diff['day']
        ) {
            if (
                0 === $diff['hour']
                && 0 === $diff['minute']
            ) {
                $result[] = $this->translator->translate('less than a minute');
            } elseif (0 === $diff['hour']) {
                $units = ['minute', 'second'];
            } else {
                $units = ['hour', 'minute'];
            }
        } else {
            $units = ['year', 'month', 'week', 'day', 'hour'];
        }

        foreach ($units as $unit) {
            if (0 === $diff[$unit]) {
                continue;
            }

            $result[] = sprintf(
                '%d %s',
                $diff[$unit],
                $this->translator->translatePlural($unit, $unit . 's', $diff[$unit]),
            );
        }

        if (1 < count($result)) {
            return sprintf(
                '%s %s %s',
                implode(', ', array_slice($result, 0, -1)),
                $this->translator->translate('and'),
                array_pop($result),
            );
        }

        if (1 === count($result)) {
            return $result[0];
        }

        return $this->translator->translate('unable to estimate time');
    }

    /**
     * @phpstan-ignore-next-line
     */
    private function registerTranslations(): void
    {
        // I know this is very ugly, but it works...
        $this->translator->translatePlural('year', 'years', 1);
        $this->translator->translatePlural('month', 'months', 1);
        $this->translator->translatePlural('week', 'weeks', 1);
        $this->translator->translatePlural('day', 'days', 1);
        $this->translator->translatePlural('hour', 'hours', 1);
        $this->translator->translatePlural('minute', 'minutes', 1);
        $this->translator->translatePlural('second', 'seconds', 1);
    }
}
