<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member;
use App\Repository\Activity\ActivityRepository;
use App\Tests\Integration\DatabaseTestCase;

use function array_diff;
use function array_intersect;
use function sprintf;

/**
 * The revision model means every query has to pick the right revision to anchor on, and getting it wrong leaks drafts.
 * These pin the three anchors against the seed: the public overview surfaces only activities with a live (approved)
 * revision, the admin "pending" list anchors on the WORKING revision's status (and scopes to the member's own work),
 * and the admin "approved" list returns only approved working revisions.
 *
 * Organ scoping is not asserted here: the seed assigns no organ to any revision, so there is nothing to scope by. The
 * parallel Vacancy/Company repositories share this anchoring but the career domain has no fixtures yet, so they are not
 * covered.
 */
final class ActivityRepositoryAnchoringTest extends DatabaseTestCase
{
    public function testPublicOverviewSurfacesOnlyActivitiesWithALiveRevision(): void
    {
        $overview = $this->repository()->findForOverview(
            false,
            null,
            '',
            'en',
            null,
            [],
            null,
            false,
            null,
            null,
            100,
            0,
        );
        $ids = $this->ids($overview);

        self::assertNotEmpty($ids);
        // The non-approved activities (no live revision) never surface publicly, even though their dates are upcoming.
        self::assertEmpty(array_intersect(
            $ids,
            $this->activityIds(false),
        ));
    }

    public function testFindPendingForAdminWithAllReturnsEveryNonApprovedActivity(): void
    {
        $pending = $this->repository()->findPendingForAdmin(
            $this->member(8025),
            [],
            true,
        );

        // The "pending" list anchors on the working revision: exactly the activities whose head is not yet approved.
        self::assertEqualsCanonicalizing(
            $this->activityIds(false),
            $this->ids($pending),
        );
    }

    public function testFindApprovedForAdminWithAllReturnsOnlyApprovedActivities(): void
    {
        $approved = $this->repository()->findApprovedForAdmin(
            $this->member(8025),
            [],
            true,
            1,
            100,
        );

        self::assertEqualsCanonicalizing(
            $this->activityIds(true),
            $this->ids($approved),
        );
    }

    public function testFindPendingForAdminScopedToAMemberShowsOnlyTheirOwnWork(): void
    {
        $pending = $this->repository()->findPendingForAdmin(
            $this->member(8013),
            [],
            false,
        );
        $ids = $this->ids($pending);

        // Their own (in-review) activity is visible ...
        self::assertContains(
            $this->nonApprovedActivityIdByCreator(8013),
            $ids,
        );
        // ... another member's (rejected) activity is not.
        self::assertNotContains(
            $this->nonApprovedActivityIdByCreator(8012),
            $ids,
        );
        // ... and nothing approved slipped in.
        self::assertEmpty(array_diff(
            $ids,
            $this->activityIds(false),
        ));
    }

    private function repository(): ActivityRepository
    {
        return $this->entityManager->getRepository(Activity::class);
    }

    private function member(int $lidnr): Member
    {
        $member = $this->entityManager->getRepository(Member::class)->find($lidnr);
        self::assertInstanceOf(
            Member::class,
            $member,
            sprintf(
                'The seed is expected to contain member %d.',
                $lidnr,
            ),
        );

        return $member;
    }

    /**
     * The ids of the activities whose WORKING revision is (not) approved, straight from the database, to compare the
     * repository's anchoring against.
     *
     * @return int[]
     */
    private function activityIds(bool $approved): array
    {
        $activities = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->join(
                'a.currentRevision',
                'cr',
            )
            ->where($approved ? 'cr.status = :approved' : 'cr.status <> :approved')
            ->setParameter(
                'approved',
                RevisionStatus::Approved->value,
            )
            ->getQuery()
            ->getResult();

        return $this->ids($activities);
    }

    private function nonApprovedActivityIdByCreator(int $lidnr): int
    {
        $activity = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->join(
                'a.currentRevision',
                'cr',
            )
            ->where('IDENTITY(a.creator) = :lidnr')
            ->andWhere('cr.status <> :approved')
            ->setParameter(
                'lidnr',
                $lidnr,
            )
            ->setParameter(
                'approved',
                RevisionStatus::Approved->value,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
            sprintf(
                'The seed is expected to contain a non-approved activity created by member %d.',
                $lidnr,
            ),
        );

        return (int) $activity->getId();
    }

    /**
     * @param iterable<mixed> $activities
     *
     * @return int[]
     */
    private function ids(iterable $activities): array
    {
        $ids = [];
        foreach ($activities as $activity) {
            self::assertInstanceOf(
                Activity::class,
                $activity,
            );
            $ids[] = (int) $activity->getId();
        }

        return $ids;
    }
}
