<?php

declare(strict_types=1);

namespace App\Service\Activity;

use DateTime;

use function sprintf;

/**
 * The two moments a reserved day is measured against, in one place so the warning and the release cannot drift apart.
 *
 * Association policy asks for a budget in hand early enough to be discussed at a board meeting at least four weeks
 * before the activity. Board meetings are not known in advance, so the website cannot resolve that into a date; it
 * counts the four weeks from the day itself and warns then, and gives the two further weeks the option calendar has
 * always given before letting the day go.
 */
final readonly class OptionBudgetSchedule
{
    /**
     * The policy's four weeks, counted back from the day the activity would be on.
     */
    public const int LEAD_DAYS = 28;

    /**
     * How much longer a day is held after the warning before it is released, which is the grace the calendar has
     * always given.
     */
    public const int GRACE_DAYS = 14;

    /**
     * Days starting on or before this are close enough to warn about.
     */
    public function remindBefore(): DateTime
    {
        return new DateTime(sprintf(
            '+%d days',
            self::LEAD_DAYS,
        ));
    }

    /**
     * Days starting on or before this have run out of road.
     */
    public function lapseBefore(): DateTime
    {
        return new DateTime(sprintf(
            '+%d days',
            self::LEAD_DAYS - self::GRACE_DAYS,
        ));
    }
}
