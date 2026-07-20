<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Decision\AssociationYear;
use App\Repository\Activity\ActivityRepository;
use App\Tests\Integration\DatabaseTestCase;
use Doctrine\ORM\Tools\Pagination\Paginator;

use function array_map;
use function array_merge;
use function array_unique;
use function iterator_to_array;

/**
 * The overview query has three time modes: the default upcoming feed (past = false), a single association-year window,
 * and the cross-year search (past = null) that drops the now-window entirely. These pin the cross-year mode against the
 * seed, and pin the year list that feeds the switcher (which now includes upcoming years).
 */
final class ActivityOverviewQueryTest extends DatabaseTestCase
{
    public function testCrossYearModeReturnsUpcomingAndPastTogetherNewestFirst(): void
    {
        $all = $this->overview(null);
        $allIds = $this->ids($all);
        $upcomingIds = $this->ids($this->overview(false));
        $pastIds = $this->ids($this->overview(true));

        // The seed always has upcoming approved activities; past may be empty, but the union must hold exactly.
        self::assertNotEmpty($upcomingIds);
        self::assertEqualsCanonicalizing(
            array_unique(array_merge(
                $upcomingIds,
                $pastIds,
            )),
            $allIds,
        );

        // Newest first: begin times are non-increasing.
        $activities = iterator_to_array(
            $all->getIterator(),
            false,
        );
        $previous = null;
        foreach ($activities as $activity) {
            $begin = $activity->getBeginTime();
            if (null !== $previous) {
                self::assertGreaterThanOrEqual(
                    $begin->getTimestamp(),
                    $previous->getTimestamp(),
                    'The cross-year search must list activities newest first.',
                );
            }

            $previous = $begin;
        }
    }

    public function testYearArchiveExcludesThatYearsUpcomingActivities(): void
    {
        // A known upcoming activity from the seed: its own association-year archive must not list it, because an
        // archive is past only, even for the current year.
        $upcoming = iterator_to_array(
            $this->overview(false)->getIterator(),
            false,
        );
        self::assertNotEmpty($upcoming);

        $activity = $upcoming[0];
        $window = AssociationYear::fromYear(AssociationYear::fromDate($activity->getBeginTime())->getYear());

        $archiveIds = $this->ids($this->repository()->findForOverview(
            true,
            null,
            '',
            'en',
            null,
            [],
            null,
            false,
            $window->getStartDate(),
            $window->getEndDate(),
            200,
            0,
        ));

        self::assertNotContains(
            (int) $activity->getId(),
            $archiveIds,
        );
    }

    /**
     * @return Paginator<Activity>
     */
    private function overview(?bool $past): Paginator
    {
        return $this->repository()->findForOverview(
            $past,
            null,
            '',
            'en',
            null,
            [],
            null,
            false,
            null,
            null,
            200,
            0,
        );
    }

    /**
     * @param Paginator<Activity> $paginator
     *
     * @return int[]
     */
    private function ids(Paginator $paginator): array
    {
        return array_map(
            static fn (Activity $activity): int => (int) $activity->getId(),
            iterator_to_array(
                $paginator->getIterator(),
                false,
            ),
        );
    }

    private function repository(): ActivityRepository
    {
        return $this->entityManager->getRepository(Activity::class);
    }
}
