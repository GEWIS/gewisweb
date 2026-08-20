<?php

declare(strict_types=1);

namespace App\ViewModel\Activity\Calendar;

use DateTimeImmutable;

/**
 * A month of the option calendar, already cut into whole weeks.
 *
 * Weeks start on Monday, which is how the association reads a week and how the paper calendar was drawn.
 */
final readonly class CalendarMonth
{
    /**
     * @param CalendarDay[][] $weeks
     */
    public function __construct(
        public DateTimeImmutable $firstDay,
        public array $weeks,
    ) {
    }

    public function previous(): string
    {
        return $this->firstDay->modify('-1 month')->format('Y-m');
    }

    public function next(): string
    {
        return $this->firstDay->modify('+1 month')->format('Y-m');
    }
}
