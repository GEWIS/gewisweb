<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\Enums\CalendarEntryKind;
use App\Entity\Activity\Enums\DateOptionStatus;
use App\Entity\Application\Enums\Languages;
use App\Entity\Decision\Organ;
use App\Repository\Activity\ActivityDateOptionRepository;
use App\Repository\Activity\ActivityRepository;
use App\ViewModel\Activity\Calendar\AgendaEvent;
use App\ViewModel\Activity\Calendar\CalendarDay;
use App\ViewModel\Activity\Calendar\CalendarEntry;
use App\ViewModel\Activity\Calendar\CalendarMonth;
use DateTime;
use DateTimeImmutable;

/**
 * Lays a month of the option calendar out: six weeks of days, each carrying what is already in the agenda and what
 * bodies are asking for.
 *
 * Two queries and a cached agenda, whatever the month holds. Everything that touches the visible stretch is read once
 * and then spread across the days it covers, so something running from a Friday to a Sunday really is drawn on all
 * three days rather than only on the day it starts. That is what makes a week worth looking at: a body deciding
 * whether to ask for the Saturday needs to see the thing that started on the Friday.
 *
 * Rank is the order the bodies asked in, which is what first dibs means. It is worked out per day rather than stored,
 * because it changes the moment somebody withdraws, and it is never enforced anywhere: the board decides.
 */
final readonly class CalendarMonthBuilder
{
    private const int WEEKS = 6;

    public function __construct(
        private ActivityDateOptionRepository $dateOptionRepository,
        private ActivityRepository $activityRepository,
        private AgendaFeed $agendaFeed,
    ) {
    }

    public function build(
        DateTimeImmutable $anyDayInTheMonth,
        ?Organ $organ = null,
    ): CalendarMonth {
        $firstDay = $anyDayInTheMonth->modify('first day of this month')->setTime(
            0,
            0,
        );
        $gridStart = $firstDay->modify('monday this week');

        // A month never needs more than six weeks, and always drawing six keeps the grid from jumping about as the
        // months change.
        $gridEnd = $gridStart->modify('+' . (self::WEEKS * 7 - 1) . ' days');

        $optionsByDay = $this->optionsByDay(
            $gridStart,
            $gridEnd,
            $organ,
        );
        $activitiesByDay = $this->activitiesByDay(
            $gridStart,
            $gridEnd,
            $organ,
        );
        // Filtering by body hides this layer: an exam week belongs to nobody, and showing it under one body's name
        // would be a lie.
        $agendaByDay = null === $organ
            ? $this->agendaByDay(
                $gridStart,
                $gridEnd,
            )
            : [];

        $today = new DateTimeImmutable('today');
        $weeks = [];

        for ($week = 0; $week < self::WEEKS; ++$week) {
            $days = [];

            for ($weekday = 0; $weekday < 7; ++$weekday) {
                $date = $gridStart->modify('+' . ($week * 7 + $weekday) . ' days');
                $key = $date->format('Y-m-d');

                $days[] = new CalendarDay(
                    $date,
                    $date->format('Y-m') === $firstDay->format('Y-m'),
                    $key === $today->format('Y-m-d'),
                    5 <= $weekday,
                    [
                        ...($activitiesByDay[$key] ?? []),
                        ...($agendaByDay[$key] ?? []),
                        ...($optionsByDay[$key] ?? []),
                    ],
                );
            }

            $weeks[] = $days;
        }

        return new CalendarMonth(
            $firstDay,
            $weeks,
        );
    }

    /**
     * @return array<string, CalendarEntry[]>
     */
    private function optionsByDay(
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        ?Organ $organ,
    ): array {
        $options = $this->dateOptionRepository->findOverlapping(
            DateTime::createFromInterface($from),
            DateTime::createFromInterface($until),
            $organ,
        );

        // Rank is per day and per body's turn in the queue, so it is counted while spreading rather than read off a
        // column that would go stale the moment somebody withdrew.
        $rankPerDay = [];
        $byDay = [];

        foreach ($options as $option) {
            $spread = $this->daysCovered(
                $option,
                $from,
                $until,
            );

            foreach ($spread as $key => $position) {
                $rankPerDay[$key] ??= 0;
                ++$rankPerDay[$key];

                $byDay[$key][] = $this->entryFor(
                    $option,
                    $rankPerDay[$key],
                    $position['continuesBefore'],
                    $position['continuesAfter'],
                );
            }
        }

        return $byDay;
    }

    /**
     * @return array<string, CalendarEntry[]>
     */
    private function activitiesByDay(
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        ?Organ $organ,
    ): array {
        $activities = $this->activityRepository->findLiveBetween(
            DateTime::createFromInterface($from),
            DateTime::createFromInterface($until->modify('+1 day')),
        );

        $byDay = [];

        foreach ($activities as $activity) {
            if (
                null !== $organ
                && $activity->getOrgan()?->getId() !== $organ->getId()
            ) {
                continue;
            }

            $begins = $activity->getBeginTime();
            $ends = $activity->getEndTime();

            $cursor = DateTimeImmutable::createFromInterface($begins)->setTime(
                0,
                0,
            );
            $last = DateTimeImmutable::createFromInterface($ends)->setTime(
                0,
                0,
            );

            while ($cursor <= $last) {
                if (
                    $cursor >= $from
                    && $cursor <= $until
                ) {
                    $byDay[$cursor->format('Y-m-d')][] = $this->activityEntry(
                        $activity,
                        $cursor > $from && $cursor->format('Y-m-d') !== $begins->format('Y-m-d'),
                        $cursor->format('Y-m-d') !== $ends->format('Y-m-d'),
                    );
                }

                $cursor = $cursor->modify('+1 day');
            }
        }

        return $byDay;
    }

    /**
     * What the association's own agenda has in this stretch, spread over the days it covers. Read from the copy the
     * sync command keeps; nothing here ever waits on somebody else's server.
     *
     * @return array<string, CalendarEntry[]>
     */
    private function agendaByDay(
        DateTimeImmutable $from,
        DateTimeImmutable $until,
    ): array {
        $byDay = [];

        foreach (
            $this->agendaFeed->eventsBetween(
                $from,
                $until,
            ) as $event
        ) {
            $cursor = $event->startsOn;

            while ($cursor <= $event->endsOn) {
                if (
                    $cursor >= $from
                    && $cursor <= $until
                ) {
                    $byDay[$cursor->format('Y-m-d')][] = $this->agendaEntry(
                        $event,
                        $cursor > $event->startsOn,
                        $cursor < $event->endsOn,
                    );
                }

                $cursor = $cursor->modify('+1 day');
            }
        }

        return $byDay;
    }

    private function agendaEntry(
        AgendaEvent $event,
        bool $continuesBefore,
        bool $continuesAfter,
    ): CalendarEntry {
        return new CalendarEntry(
            CalendarEntryKind::AgendaItem,
            $event->title,
            '',
            null,
            null,
            null,
            $continuesBefore,
            $continuesAfter,
        );
    }

    /**
     * The days one option takes up that are on screen, each saying whether the run carries on past it.
     *
     * @return array<string, array{continuesBefore: bool, continuesAfter: bool}>
     */
    private function daysCovered(
        ActivityDateOption $option,
        DateTimeImmutable $from,
        DateTimeImmutable $until,
    ): array {
        $begins = DateTimeImmutable::createFromInterface($option->getBeginsAt())->setTime(
            0,
            0,
        );
        $ends = DateTimeImmutable::createFromInterface($option->getEndsAt())->setTime(
            0,
            0,
        );

        $covered = [];
        $cursor = $begins;

        while ($cursor <= $ends) {
            if (
                $cursor >= $from
                && $cursor <= $until
            ) {
                $covered[$cursor->format('Y-m-d')] = [
                    'continuesBefore' => $cursor > $begins,
                    'continuesAfter' => $cursor < $ends,
                ];
            }

            $cursor = $cursor->modify('+1 day');
        }

        return $covered;
    }

    private function entryFor(
        ActivityDateOption $option,
        int $rank,
        bool $continuesBefore,
        bool $continuesAfter,
    ): CalendarEntry {
        $proposal = $option->getProposal();

        return new CalendarEntry(
            DateOptionStatus::Approved === $option->getStatus()
                ? CalendarEntryKind::ReservedDay
                : CalendarEntryKind::RequestedDay,
            $proposal->getName(),
            $proposal->getOrgan()?->getAbbr() ?? '',
            $option->getTimeOfDay()->value,
            $proposal->getId(),
            $rank,
            $continuesBefore,
            $continuesAfter,
        );
    }

    private function activityEntry(
        Activity $activity,
        bool $continuesBefore,
        bool $continuesAfter,
    ): CalendarEntry {
        return new CalendarEntry(
            CalendarEntryKind::FixedActivity,
            $activity->getName()->getText(Languages::current()) ?? '',
            $activity->getOrgan()?->getAbbr() ?? '',
            null,
            null,
            null,
            $continuesBefore,
            $continuesAfter,
        );
    }
}
