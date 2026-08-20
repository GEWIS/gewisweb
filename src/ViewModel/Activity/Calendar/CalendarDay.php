<?php

declare(strict_types=1);

namespace App\ViewModel\Activity\Calendar;

use DateTimeImmutable;

use function count;

/**
 * One cell of the month grid.
 *
 * The days either side of the month are drawn too, because a week runs across the turn of a month and a body asking
 * for the 31st needs to see it from both. They carry their entries like any other day, they are only drawn quieter.
 */
final readonly class CalendarDay
{
    /**
     * @param CalendarEntry[] $entries
     */
    public function __construct(
        public DateTimeImmutable $date,
        public bool $inMonth,
        public bool $isToday,
        public bool $isWeekend,
        public array $entries,
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->entries;
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
