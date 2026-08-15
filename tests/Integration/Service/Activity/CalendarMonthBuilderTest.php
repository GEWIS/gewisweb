<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Activity;

use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\CalendarEntryKind;
use App\Entity\Activity\Enums\TimeOfDay;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Service\Activity\CalendarMonthBuilder;
use App\Tests\Integration\DatabaseTestCase;
use App\ViewModel\Activity\Calendar\CalendarDay;
use DateTime;
use DateTimeImmutable;

use function array_keys;

/**
 * Laying a month out.
 *
 * The two things worth pinning are the ones the mock-up this was drawn from could not do. Something running across
 * several days has to appear on every one of them, with a marker where the run carries on past the edge of the week,
 * or a body looking at the Saturday cannot see the thing that started on the Friday. And the days either side of the
 * month are real days carrying real blocks, because a week runs across the turn of a month.
 */
final class CalendarMonthBuilderTest extends DatabaseTestCase
{
    public function testAMonthIsAlwaysSixWholeWeeksStartingOnMonday(): void
    {
        $month = $this->builder()->build(new DateTimeImmutable('2026-11-15'));

        self::assertCount(
            6,
            $month->weeks,
        );

        foreach ($month->weeks as $week) {
            self::assertCount(
                7,
                $week,
            );
            self::assertSame(
                '1',
                $week[0]->date->format('N'),
                'A week starts on Monday.',
            );
        }
    }

    public function testTheDaysEitherSideOfTheMonthAreDrawnAndMarked(): void
    {
        $month = $this->builder()->build(new DateTimeImmutable('2026-11-15'));

        $outside = 0;
        foreach ($this->allDays($month->weeks) as $day) {
            if ($day->inMonth) {
                continue;
            }

            ++$outside;
        }

        self::assertGreaterThan(
            0,
            $outside,
            'November 2026 does not start on a Monday, so the grid has to show days of the months either side.',
        );
    }

    public function testSomethingRunningAcrossDaysIsDrawnOnEachOfThemWithContinuationMarkers(): void
    {
        $proposal = $this->aProposalSpanning(
            new DateTime('2026-11-11'),
            new DateTime('2026-11-13'),
        );

        $month = $this->builder()->build(new DateTimeImmutable('2026-11-15'));
        $found = [];

        foreach ($this->allDays($month->weeks) as $day) {
            foreach ($day->entries as $entry) {
                if ($entry->proposalId !== $proposal->getId()) {
                    continue;
                }

                $found[$day->date->format('Y-m-d')] = $entry;
            }
        }

        self::assertSame(
            [
                '2026-11-11',
                '2026-11-12',
                '2026-11-13',
            ],
            array_keys($found),
        );

        self::assertFalse($found['2026-11-11']->continuesBefore);
        self::assertTrue($found['2026-11-11']->continuesAfter);
        self::assertTrue($found['2026-11-12']->continuesBefore);
        self::assertTrue($found['2026-11-12']->continuesAfter);
        self::assertTrue($found['2026-11-13']->continuesBefore);
        self::assertFalse($found['2026-11-13']->continuesAfter);
    }

    /**
     * First dibs is the order the bodies asked in, counted per day, never stored: it changes the moment one of them
     * withdraws.
     */
    public function testTheDayEveryBodyWantsRanksThemInTheOrderTheyAsked(): void
    {
        $month = $this->builder()->build(new DateTimeImmutable('2026-11-19'));

        foreach ($this->allDays($month->weeks) as $day) {
            if ('2026-11-19' !== $day->date->format('Y-m-d')) {
                continue;
            }

            $ranks = [];
            foreach ($day->entries as $entry) {
                if (CalendarEntryKind::FixedActivity === $entry->kind) {
                    continue;
                }

                $ranks[] = $entry->rank;
            }

            self::assertSame(
                [
                    1,
                    2,
                    3,
                ],
                $ranks,
                'The seed has three bodies asking for this day.',
            );

            return;
        }

        self::fail('The grid for November 2026 has to contain the nineteenth.');
    }

    private function aProposalSpanning(
        DateTime $from,
        DateTime $until,
    ): ActivityProposal {
        $period = $this->entityManager->getRepository(OptionPeriod::class)->findOpenAt(new DateTime())[0];
        $organ = $this->entityManager->getRepository(Organ::class)->findOneBy(['abbr' => 'KEUR']);
        self::assertInstanceOf(
            Organ::class,
            $organ,
        );
        $member = $this->entityManager->getRepository(Member::class)->find(8025);
        self::assertInstanceOf(
            Member::class,
            $member,
        );

        $proposal = new ActivityProposal();
        $proposal->setPeriod($period);
        $proposal->setOrgan($organ);
        $proposal->setCreatedBy($member);
        $proposal->setName('Een weekend weg');

        $option = new ActivityDateOption();
        $option->setBeginsAt($from);
        $option->setEndsAt($until);
        $option->setTimeOfDay(TimeOfDay::MultipleDays);
        $proposal->addDateOption($option);

        $this->entityManager->persist($proposal);
        $this->entityManager->flush();

        return $proposal;
    }

    /**
     * @param CalendarDay[][] $weeks
     *
     * @return CalendarDay[]
     */
    private function allDays(array $weeks): array
    {
        $days = [];
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                $days[] = $day;
            }
        }

        return $days;
    }

    private function builder(): CalendarMonthBuilder
    {
        return self::getContainer()->get(CalendarMonthBuilder::class);
    }
}
