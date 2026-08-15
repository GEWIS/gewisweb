<?php

declare(strict_types=1);

namespace App\ViewModel\Activity\Calendar;

use App\Entity\Activity\Enums\CalendarEntryKind;

/**
 * One block in a day of the option calendar.
 *
 * Whatever spans several days is drawn on every day it covers, with a marker on the ends that run past the edge of
 * what is on screen, so a week that only shows the middle of something still says it carries on.
 */
final readonly class CalendarEntry
{
    public function __construct(
        public CalendarEntryKind $kind,
        public string $title,
        public string $by,
        public ?string $timeOfDay,
        public ?int $proposalId,
        public ?int $rank,
        public bool $continuesBefore,
        public bool $continuesAfter,
    ) {
    }

    /**
     * Whether this is the first body to ask for the day, which is what first dibs means. Only ever shown; the board
     * decides.
     */
    public function isFirstInLine(): bool
    {
        return 1 === $this->rank;
    }
}
