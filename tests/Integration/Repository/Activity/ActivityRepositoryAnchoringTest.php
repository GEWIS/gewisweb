<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Repository\Activity\ActivityRepository;
use App\Tests\Integration\DatabaseTestCase;

use function array_diff;
use function array_intersect;
use function sprintf;

/**
 * The revision model means every query has to pick the right revision to anchor on, and getting it wrong leaks drafts.
 * These pin the anchors against the seed: the public overview surfaces only activities with a live (approved)
 * revision, the admin "pending" list anchors on the WORKING revision's status, and the admin "approved" list returns
 * only approved working revisions. Visibility scoping is covered too -- by the member's own work, and by the working
 * revision's organ (the seed assigns the workflow examples to GETÉST and the disjoint KEUR, so organ scope can be
 * shown to be specific).
 *
 * The parallel Vacancy/Company repositories share this anchoring but the career domain has no fixtures yet, so they
 * are not covered.
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

    public function testFindPendingForAdminScopedByOrganShowsThatOrgansDraftsToANonAuthorMember(): void
    {
        $getest = $this->organId('GETÉST');

        // Member 8005 is in GETÉST but creates/authors nothing non-approved, so every pending activity they see is
        // there purely via the organ scope -- exactly GETÉST's non-approved work.
        $pending = $this->repository()->findPendingForAdmin(
            $this->member(8005),
            [$getest],
            false,
        );

        self::assertEqualsCanonicalizing(
            $this->nonApprovedActivityIdsForOrgan($getest),
            $this->ids($pending),
        );
    }

    public function testOrganScopeIsSpecificToTheGivenOrgan(): void
    {
        $getest = $this->organId('GETÉST');
        $keur = $this->organId('KEUR');

        $viaGetest = $this->ids($this->repository()->findPendingForAdmin(
            $this->member(8005),
            [$getest],
            false,
        ));
        $viaKeur = $this->ids($this->repository()->findPendingForAdmin(
            $this->member(8025),
            [$keur],
            false,
        ));

        // Each organ's member sees their own organ's drafts ...
        self::assertEqualsCanonicalizing(
            $this->nonApprovedActivityIdsForOrgan($getest),
            $viaGetest,
        );
        self::assertEqualsCanonicalizing(
            $this->nonApprovedActivityIdsForOrgan($keur),
            $viaKeur,
        );
        // ... and never the other organ's (organ scope is not "any organ I am in sees everything").
        self::assertEmpty(array_intersect(
            $viaGetest,
            $this->nonApprovedActivityIdsForOrgan($keur),
        ));
    }

    public function testWithoutOrganScopeOrganMembershipGrantsNothing(): void
    {
        // Same member as the organ-scoped test, but with no organ passed: membership alone grants no visibility, and
        // 8005 created/authored nothing non-approved, so the pending list is empty.
        self::assertEmpty($this->ids($this->repository()->findPendingForAdmin(
            $this->member(8005),
            [],
            false,
        )));
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

    /**
     * The ids of the non-approved activities whose WORKING revision is organised by the given organ, to compare the
     * repository's organ scoping against.
     *
     * @return int[]
     */
    private function nonApprovedActivityIdsForOrgan(int $organId): array
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
            ->where('cr.status <> :approved')
            ->andWhere('IDENTITY(cr.organ) = :organId')
            ->setParameter(
                'approved',
                RevisionStatus::Approved->value,
            )
            ->setParameter(
                'organId',
                $organId,
            )
            ->getQuery()
            ->getResult();

        return $this->ids($activities);
    }

    private function organId(string $abbr): int
    {
        $organ = $this->entityManager->getRepository(Organ::class)->findOneBy(['abbr' => $abbr]);
        self::assertInstanceOf(
            Organ::class,
            $organ,
            sprintf(
                'The seed is expected to contain the %s organ.',
                $abbr,
            ),
        );

        return (int) $organ->getId();
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
