<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Activity;

use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\DrawCutoffRule;
use App\Entity\Activity\SignupList;
use App\Entity\Decision\Member;
use App\Service\Activity\DrawManager;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;

/**
 * The shared draw runner behind both the board's manual draw and the automated deadline draw. Pinned here: the guard
 * differences between the two paths (a manual draw needs close or a passed draw moment, an automated draw its own
 * moment), the one-shot lock, the audit stamp (a member for manual, null for automated), the refusal to touch
 * manual-allocation lists and that the manual fallback draws the same cutoff snapshot as an on-time automated draw.
 * Dates are pinned explicitly per scenario, so nothing depends on seed freshness.
 *
 * Activity #9 (the Gala) has an open limited list (#6, capacity 2, four waitlisted sign-ups); activity #13 (the
 * Excursion) has a closed limited list (#11, capacity 2, four sign-ups).
 */
final class DrawManagerTest extends DatabaseTestCase
{
    public function testManualDrawOnAClosedListAdmitsUpToCapacityAndStampsTheMember(): void
    {
        $this->pinDates(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
        );
        $list = $this->list(11);
        $board = $this->member(8025);

        self::assertTrue($this->drawManager()->drawManually(
            $list,
            AllocationMethod::ConditionalDraw,
            $board,
        ));

        self::assertNotNull($list->getDrawnAt());
        self::assertSame(
            $board->getLidnr(),
            $list->getDrawnBy()?->getLidnr(),
        );
        self::assertSame(
            2,
            $this->drawnCount($list),
        );
    }

    public function testADrawIsAOneShotEvent(): void
    {
        $this->pinDates(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
        );
        $list = $this->list(11);
        $board = $this->member(8025);

        self::assertTrue($this->drawManager()->drawManually(
            $list,
            AllocationMethod::ConditionalDraw,
            $board,
        ));
        // The runner refreshes the row inside its lock, so compare at the column's (second) precision.
        $drawnAt = $list->getDrawnAt()?->format('Y-m-d H:i:s');

        // The second draw bails on the lock recheck: the result of a lottery never changes.
        self::assertFalse($this->drawManager()->drawManually(
            $list,
            AllocationMethod::ConditionalDraw,
            $board,
        ));
        self::assertSame(
            $drawnAt,
            $list->getDrawnAt()?->format('Y-m-d H:i:s'),
        );
    }

    public function testManualDrawIsRefusedBeforeCloseWhenNoDrawMomentHasPassed(): void
    {
        $this->pinDates(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
        );
        $list = $this->list(6);

        // On-close list, still open: a board member cannot pre-empt the announced moment.
        self::assertFalse($this->drawManager()->drawManually(
            $list,
            AllocationMethod::ConditionalDraw,
            $this->member(8025),
        ));
        self::assertNull($list->getDrawnAt());
    }

    public function testManualDrawIsAllowedBeforeCloseOnceTheDrawMomentHasPassed(): void
    {
        // The if-full-before cutoff passed an hour ago but the automated draw did not run (say the scheduler was
        // down): the board may step in even though sign-up is still open.
        $this->pinDates(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );
        $list = $this->list(6);

        self::assertTrue($this->drawManager()->drawManually(
            $list,
            AllocationMethod::ConditionalDraw,
            $this->member(8025),
        ));
    }

    public function testManualDrawIsRefusedForAMismatchedMethod(): void
    {
        $this->pinDates(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
        );
        $list = $this->list(11);

        // A stale first-come button on a list since reconfigured to a conditional draw must not run anything.
        self::assertFalse($this->drawManager()->drawManually(
            $list,
            AllocationMethod::FirstComeFirstServed,
            $this->member(8025),
        ));
        self::assertNull($list->getDrawnAt());
    }

    public function testAutomaticDrawIsRefusedBeforeTheDrawMoment(): void
    {
        $this->pinDates(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
        );

        self::assertFalse($this->drawManager()->drawAutomatically($this->list(6)));
    }

    public function testAutomaticDrawAdmitsUpToCapacityWithoutAMemberStamp(): void
    {
        $this->pinDates(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
        );
        $list = $this->list(11);

        self::assertTrue($this->drawManager()->drawAutomatically($list));

        self::assertNotNull($list->getDrawnAt());
        self::assertNull($list->getDrawnBy());
        self::assertSame(
            2,
            $this->drawnCount($list),
        );
        // The rest is waitlisted with attendance cleared: you cannot have attended without being admitted.
        foreach ($list->getSignUps() as $signup) {
            if ($signup->isDrawn()) {
                continue;
            }

            self::assertFalse($signup->isPresent());
        }
    }

    public function testAutomaticDrawNeverTouchesAManualAllocationMethod(): void
    {
        $list = $this->manualMethodList();

        self::assertFalse($this->drawManager()->drawAutomatically($list));
        self::assertNull($list->getDrawnAt());
    }

    public function testManualDrawUsesTheSameCutoffSnapshot(): void
    {
        // The board's fallback for a missed automated draw must produce what an on-time draw would have: only the two
        // sign-ups from before the cutoff (deliberately the LAST two by id) join the lottery -- and exactly fill the
        // two places -- while the two that arrived after the cutoff never displace them.
        $ids = $this->signupIds(6);
        $this->pinSignupCreatedAt(
            $ids[2],
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $ids[3],
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $ids[0],
            '-30 minutes',
        );
        $this->pinSignupCreatedAt(
            $ids[1],
            '-20 minutes',
        );
        $this->pinDates(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );
        $list = $this->list(6);

        self::assertTrue($this->drawManager()->drawManually(
            $list,
            AllocationMethod::ConditionalDraw,
            $this->member(8025),
        ));

        self::assertSame(
            [
                $ids[2],
                $ids[3],
            ],
            $this->drawnIds($list),
        );
    }

    private function drawManager(): DrawManager
    {
        return self::getContainer()->get(DrawManager::class);
    }

    private function list(int $listId): SignupList
    {
        $list = $this->entityManager->getRepository(SignupList::class)->find($listId);
        self::assertInstanceOf(
            SignupList::class,
            $list,
        );

        return $list;
    }

    private function manualMethodList(): SignupList
    {
        $list = $this->entityManager->createQueryBuilder()
            ->select('sl')
            ->from(
                SignupList::class,
                'sl',
            )
            ->where('sl.allocationMethod = :method')
            ->setParameter(
                'method',
                AllocationMethod::ExternalParty,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            SignupList::class,
            $list,
            'The seed is expected to contain an external-party sign-up list.',
        );

        return $list;
    }

    private function member(int $lidnr): Member
    {
        $member = $this->entityManager->getRepository(Member::class)->find($lidnr);
        self::assertInstanceOf(
            Member::class,
            $member,
        );

        return $member;
    }

    private function drawnCount(SignupList $list): int
    {
        $drawn = 0;
        foreach ($list->getSignUps() as $signup) {
            if (!$signup->isDrawn()) {
                continue;
            }

            ++$drawn;
        }

        return $drawn;
    }

    /**
     * The admitted sign-up ids of a list, in id order.
     *
     * @return list<int>
     */
    private function drawnIds(SignupList $list): array
    {
        $ids = [];
        foreach ($list->getSignUps() as $signup) {
            if (!$signup->isDrawn()) {
                continue;
            }

            $ids[] = (int) $signup->getId();
        }

        return $ids;
    }

    /**
     * All sign-up ids of a list, in id (arrival) order.
     *
     * @return list<int>
     */
    private function signupIds(int $listId): array
    {
        $ids = [];
        foreach ($this->list($listId)->getSignUps() as $signup) {
            $ids[] = (int) $signup->getId();
        }

        return $ids;
    }

    /**
     * Pin when a sign-up was created, directly in the database (rolled back with the test), so a late draw's cutoff
     * snapshot can be exercised against explicit participation moments. Clears the entity manager so subsequent reads
     * see the updated row.
     */
    private function pinSignupCreatedAt(
        int $signupId,
        string $modifier,
    ): void {
        $this->entityManager->getConnection()->update(
            'Signup',
            ['createdAt' => $this->sqlDateTime($modifier)],
            ['id' => $signupId],
        );

        $this->entityManager->clear();
    }

    /**
     * Pin a seeded list's timing/cutoff configuration (and its activity's end, which bounds the admission window) to
     * explicit now-relative moments, directly in the database (rolled back with the test). Clears the entity manager
     * so the subsequent reads see the updated rows.
     */
    private function pinDates(
        int $listId,
        ?string $closeDate = null,
        ?string $endTime = null,
        ?DrawCutoffRule $rule = null,
        ?string $cutoffAt = null,
    ): void {
        $revisionId = $this->list($listId)->getRevision()->getId();
        $connection = $this->entityManager->getConnection();

        $fields = [
            'closeDate' => null === $closeDate ? null : $this->sqlDateTime($closeDate),
            'drawCutoffRule' => $rule?->value,
            'drawCutoffAt' => null === $cutoffAt ? null : $this->sqlDateTime($cutoffAt),
        ];
        $data = [];
        foreach ($fields as $field => $value) {
            if (null === $value) {
                continue;
            }

            $data[$field] = $value;
        }

        $connection->update(
            'SignupList',
            $data,
            ['id' => $listId],
        );

        if (null !== $endTime) {
            $connection->update(
                'ActivityRevision',
                ['endTime' => $this->sqlDateTime($endTime)],
                ['id' => $revisionId],
            );
        }

        $this->entityManager->clear();
    }

    private function sqlDateTime(string $modifier): string
    {
        return new DateTime($modifier)->format('Y-m-d H:i:s');
    }
}
