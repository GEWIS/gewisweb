<?php

declare(strict_types=1);

namespace Application\View\Helper;

use DateTime;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Helper\AbstractHelper;

use function ceil;

class TimeDiff extends AbstractHelper
{
    public function __construct(private readonly Translator $translator)
    {
    }

    public function __invoke(
        DateTime $start,
        DateTime $end,
    ): string {
        $units = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
        ];

        $diff = $end->getTimestamp() - $start->getTimestamp();

        foreach ($units as $unit => $value) {
            if ($diff >= $value) {
                $number = ceil($diff / $value);

                return $number . ' ' . $this->translator->translatePlural($unit, $unit . 's', (int) $number);
            }
        }

        return $diff . '' . $this->translator->translatePlural('second', 'seconds', $diff);
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
    }
}
