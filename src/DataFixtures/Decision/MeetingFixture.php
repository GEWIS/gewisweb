<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

use function assert;
use function count;
use function in_array;
use function range;
use function sort;
use function sprintf;

/**
 * A realistic meeting calendar around "today": BMs every week (twelve past, one in the current week, three upcoming),
 * GMMs every month on the 20th except during the summer holiday (July through September), and CMs once per quarter of
 * the association year. Two virtual meetings sit in the past.
 *
 * Besides the `meeting-{type}-{number}` references, semantic references anchor the dependent fixtures independently of
 * the run date: `meeting-gmm-complete` (minutes and decisions), `meeting-gmm-processing` (the newest past GMM),
 * `meeting-gmm-upcoming` and `meeting-gmm-upcoming-2`, and `meeting-cm-past`/`meeting-cm-upcoming`.
 */
class MeetingFixture extends Fixture
{
    public const int FIRST_BM_NUMBER = 1800;

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $today = new DateTime('today');

        $number = self::FIRST_BM_NUMBER;
        $tuesday = new DateTime('tuesday this week');
        foreach (
            range(
                -12,
                3,
            ) as $week
        ) {
            $this->createMeeting(
                $manager,
                MeetingTypes::BV,
                $number,
                (clone $tuesday)->modify(sprintf(
                    '%+d weeks',
                    $week,
                )),
            );
            $number++;
        }

        $gmmDates = [];
        foreach (
            range(
                -12,
                5,
            ) as $month
        ) {
            $candidate = new DateTime((clone $today)->modify(sprintf(
                'first day of %+d months',
                $month,
            ))->format('Y-m-20'));

            if (
                in_array(
                    (int) $candidate->format('n'),
                    [
                        7,
                        8,
                        9,
                    ],
                    true,
                )
            ) {
                continue;
            }

            $gmmDates[] = $candidate;
        }

        $number = 205;
        $pastGmms = [];
        $upcomingGmms = [];
        foreach ($gmmDates as $date) {
            $meeting = $this->createMeeting(
                $manager,
                MeetingTypes::ALV,
                $number,
                $date,
            );
            $number++;

            if ($date < $today) {
                $pastGmms[] = $meeting;
            } else {
                $upcomingGmms[] = $meeting;
            }
        }

        assert(count($pastGmms) >= 2);
        assert(count($upcomingGmms) >= 2);
        $this->addReference(
            'meeting-gmm-processing',
            $pastGmms[count($pastGmms) - 1],
        );
        $this->addReference(
            'meeting-gmm-complete',
            $pastGmms[count($pastGmms) - 2],
        );
        $this->addReference(
            'meeting-gmm-upcoming',
            $upcomingGmms[0],
        );
        $this->addReference(
            'meeting-gmm-upcoming-2',
            $upcomingGmms[1],
        );

        // One per quarter of the association year: September-November, November-January, February-April, April-June.
        $cmDates = [];
        $year = (int) $today->format('Y');
        foreach (
            range(
                $year - 2,
                $year + 1,
            ) as $anchorYear
        ) {
            foreach (['03-10', '05-20', '10-20', '12-10'] as $anchor) {
                $candidate = new DateTime(sprintf(
                    '%d-%s',
                    $anchorYear,
                    $anchor,
                ));

                if (
                    $candidate < (clone $today)->modify('-13 months')
                    || $candidate > (clone $today)->modify('+6 months')
                ) {
                    continue;
                }

                $cmDates[] = $candidate;
            }
        }

        sort($cmDates);

        $number = 45;
        $pastCms = [];
        $upcomingCms = [];
        foreach ($cmDates as $date) {
            $meeting = $this->createMeeting(
                $manager,
                MeetingTypes::VV,
                $number,
                $date,
            );
            $number++;

            if ($date < $today) {
                $pastCms[] = $meeting;
            } else {
                $upcomingCms[] = $meeting;
            }
        }

        assert(count($pastCms) >= 1);
        assert(count($upcomingCms) >= 1);
        $this->addReference(
            'meeting-cm-past',
            $pastCms[count($pastCms) - 1],
        );
        $this->addReference(
            'meeting-cm-upcoming',
            $upcomingCms[0],
        );

        $this->createMeeting(
            $manager,
            MeetingTypes::VIRT,
            1,
            (clone $today)->modify('-4 months'),
        );
        $this->createMeeting(
            $manager,
            MeetingTypes::VIRT,
            2,
            (clone $today)->modify('-6 weeks'),
        );

        $manager->flush();
    }

    private function createMeeting(
        ObjectManager $manager,
        MeetingTypes $type,
        int $number,
        DateTime $date,
    ): Meeting {
        $meeting = new Meeting();
        $meeting->setType($type);
        $meeting->setNumber($number);
        $meeting->setDate($date);

        $manager->persist($meeting);
        $this->addReference(
            'meeting-' . $type->value . '-' . $number,
            $meeting,
        );

        return $meeting;
    }
}
