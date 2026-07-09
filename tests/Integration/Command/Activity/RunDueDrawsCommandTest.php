<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Activity;

use App\Command\Activity\RunDueDrawsCommand;
use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\DrawCutoffRule;
use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\ExternalSignupVerification;
use App\Entity\Activity\SignupList;
use App\Service\Activity\SignupManager;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The automated-draw cron must draw exactly the lists whose own draw moment has passed -- close for
 * first-come-first-served and on-close draws, the configured cutoff for the other conditional rules, which
 * legitimately fire while sign-up is still open -- and nothing else: not-yet-due lists, lists outside the admission
 * window and unverified externals are all left alone. A draw later than the announced moment must not change the
 * outcome: only those who were confirmed participants at the cutoff join the lottery, later sign-ups and later e-mail
 * confirmations continue the first-come-first-served fill in the order they became participants. Every scenario pins
 * its dates explicitly (rolled back by the test transaction), so nothing depends on how long ago the seed was loaded.
 *
 * Activity #9 (the Gala) has an open limited list (#6, capacity 2, four waitlisted sign-ups); activity #13 (the
 * Excursion) has a closed, not-yet-drawn limited list (#11, capacity 2, four sign-ups).
 */
final class RunDueDrawsCommandTest extends DatabaseTestCase
{
    public function testDrawsAClosedOnCloseListAutomatically(): void
    {
        $this->reconfigure(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
        );

        $this->runCommand();

        $list = $this->list(11);
        self::assertNotNull($list->getDrawnAt());
        // An automated draw has no board member behind it.
        self::assertNull($list->getDrawnBy());
        // Capacity is two, so exactly two of the four sign-ups are admitted.
        self::assertSame(
            2,
            $this->drawnCount($list),
        );
    }

    public function testDoesNotDrawAListWhoseMomentHasNotPassed(): void
    {
        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
        );

        $this->runCommand();

        self::assertNull($this->list(6)->getDrawnAt());
    }

    public function testIfFullBeforeDrawsAtTheCutoffWhileSignupIsStillOpen(): void
    {
        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );

        $this->runCommand();

        $list = $this->list(6);
        // Drawn even though the list is open for another week: the cutoff, not close, is the moment.
        self::assertTrue($list->isOpen());
        self::assertNotNull($list->getDrawnAt());
        self::assertNull($list->getDrawnBy());
        self::assertSame(
            2,
            $this->drawnCount($list),
        );
    }

    public function testAfterDurationOpenDrawsOnceTheDurationHasElapsed(): void
    {
        $this->reconfigure(
            6,
            openDate: '-2 hours',
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::AfterDurationOpen,
            durationHours: 1,
        );

        $this->runCommand();

        $list = $this->list(6);
        self::assertTrue($list->isOpen());
        self::assertNotNull($list->getDrawnAt());
    }

    public function testAfterDurationOpenLeavesAListWhoseDurationHasNotElapsed(): void
    {
        // The list has closed, but the after-duration rule counts from opening, not close: 48 hours after an opening
        // one hour ago is still far away, so close alone must not trigger the draw.
        $this->reconfigure(
            11,
            openDate: '-1 hour',
            closeDate: '-30 minutes',
            endTime: '+2 days',
            rule: DrawCutoffRule::AfterDurationOpen,
            durationHours: 48,
        );

        $this->runCommand();

        self::assertNull($this->list(11)->getDrawnAt());
    }

    public function testRespectsTheAdmissionWindow(): void
    {
        // Due for over two weeks, but the activity ended three days ago: past the admission window (end + 1 day) the
        // draw is pointless and must not run.
        $this->reconfigure(
            11,
            closeDate: '-2 weeks',
            endTime: '-3 days',
        );

        $this->runCommand();

        self::assertNull($this->list(11)->getDrawnAt());
    }

    public function testDrawsAFirstComeFirstServedListAtCloseInSignupOrder(): void
    {
        $this->reconfigure(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
            method: AllocationMethod::FirstComeFirstServed,
        );

        $this->runCommand();

        $list = $this->list(11);
        self::assertNotNull($list->getDrawnAt());
        // First-come-first-served admits in creation order: the two earliest sign-ups, never a shuffle.
        $expected = [];
        $actual = [];
        foreach ($list->getSignUps() as $index => $signup) {
            if ($index < 2) {
                $expected[] = $signup->getId();
            }

            if (!$signup->isDrawn()) {
                continue;
            }

            $actual[] = $signup->getId();
        }

        self::assertSame(
            $expected,
            $actual,
        );
    }

    public function testAnUnverifiedExternalIsNeitherDrawnNorTakingUpAPlace(): void
    {
        $signupManager = self::getContainer()->get(SignupManager::class);
        $external = $signupManager->createExternalSignup(
            $this->list(6),
            'Ghost Guest',
            'ghost.guest@example.org',
            [],
        );
        $externalId = (int) $external->getId();

        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );

        $this->runCommand();

        $list = $this->list(6);
        self::assertNotNull($list->getDrawnAt());
        // Two of the four members are admitted; the unverified external took part in nothing.
        self::assertSame(
            2,
            $this->drawnCount($list),
        );
        foreach ($list->getSignUps() as $signup) {
            if ($signup->getId() !== $externalId) {
                continue;
            }

            self::assertFalse($signup->isDrawn());
        }
    }

    public function testALateDrawDrawsOnlyThePoolFromBeforeTheCutoff(): void
    {
        // Participation order is inverted relative to id order: the two LAST sign-ups (by id) were there before the
        // cutoff, the two FIRST arrived after it. A draw running an hour late must lottery only the two from before
        // the cutoff -- which exactly fill the two places -- never the later arrivals an execution-time (or id-order)
        // pool would have included.
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
        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );

        $this->runCommand();

        $list = $this->list(6);
        self::assertNotNull($list->getDrawnAt());
        self::assertSame(
            [
                $ids[2],
                $ids[3],
            ],
            $this->drawnIds($list),
        );
    }

    public function testALateDrawFillsRemainingPlacesInParticipationOrder(): void
    {
        // One sign-up predates the cutoff and wins the lottery outright; the remaining place goes to the latecomer
        // who became a participant first (again inverted relative to id order), reproducing the first-come-first-served
        // fill an on-time draw would have handed out afterwards.
        $ids = $this->signupIds(6);
        $this->pinSignupCreatedAt(
            $ids[1],
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $ids[3],
            '-30 minutes',
        );
        $this->pinSignupCreatedAt(
            $ids[2],
            '-20 minutes',
        );
        $this->pinSignupCreatedAt(
            $ids[0],
            '-10 minutes',
        );
        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );

        $this->runCommand();

        self::assertSame(
            [
                $ids[1],
                $ids[3],
            ],
            $this->drawnIds($this->list(6)),
        );
    }

    public function testAnExternalConfirmedAfterTheCutoffFillsOnlyAfterEarlierParticipants(): void
    {
        // The external signed up before the cutoff but confirmed its e-mail only after it: confirmation is the moment
        // it became a participant, so it queues behind the member who arrived half an hour ago -- an on-time draw
        // followed by this confirmation would have produced exactly that.
        $external = $this->createConfirmedExternal(6);
        $externalId = (int) $external->getId();

        $memberIds = $this->memberSignupIds(6);
        $this->pinSignupCreatedAt(
            $externalId,
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $memberIds[0],
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $memberIds[1],
            '-30 minutes',
        );
        $this->pinSignupCreatedAt(
            $memberIds[2],
            '-20 minutes',
        );
        $this->pinSignupCreatedAt(
            $memberIds[3],
            '-10 minutes',
        );
        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
        );

        $this->runCommand();

        self::assertSame(
            [
                $memberIds[0],
                $memberIds[1],
            ],
            $this->drawnIds($this->list(6)),
        );
    }

    public function testAnExternalConfirmedAfterTheCutoffIsAdmittedWhenPlacesRemain(): void
    {
        // With everyone admitted there is still a place left over, so the late-confirmed external gets it as the fill
        // -- confirming after the cutoff postpones participation, it never forfeits it.
        $external = $this->createConfirmedExternal(6);
        $externalId = (int) $external->getId();

        foreach ($this->memberSignupIds(6) as $memberId) {
            $this->pinSignupCreatedAt(
                $memberId,
                '-2 hours',
            );
        }

        $this->reconfigure(
            6,
            closeDate: '+1 week',
            endTime: '+8 days',
            rule: DrawCutoffRule::IfFullBefore,
            cutoffAt: '-1 hour',
            capacity: 6,
        );

        $this->runCommand();

        self::assertContains(
            $externalId,
            $this->drawnIds($this->list(6)),
        );
    }

    public function testAFirstComeFirstServedLateDrawAdmitsPreCloseSignupsFirst(): void
    {
        // First-come-first-served draws at close in creation order -- but only among those from before the close. The
        // lowest-id sign-up postdates it (its list was reconfigured), so a late draw admits the two earliest sign-ups
        // from before the close instead of blindly taking the two lowest ids.
        $ids = $this->signupIds(11);
        $this->pinSignupCreatedAt(
            $ids[0],
            '-30 minutes',
        );
        $this->pinSignupCreatedAt(
            $ids[1],
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $ids[2],
            '-2 hours',
        );
        $this->pinSignupCreatedAt(
            $ids[3],
            '-2 hours',
        );
        $this->reconfigure(
            11,
            closeDate: '-1 hour',
            endTime: '+2 days',
            method: AllocationMethod::FirstComeFirstServed,
        );

        $this->runCommand();

        self::assertSame(
            [
                $ids[1],
                $ids[2],
            ],
            $this->drawnIds($this->list(11)),
        );
    }

    private function runCommand(): void
    {
        $tester = new CommandTester(self::getContainer()->get(RunDueDrawsCommand::class));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
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
     * The member sign-up ids of a list, in id (arrival) order -- excluding externals, for scenarios that add their
     * own external next to the seeded members.
     *
     * @return list<int>
     */
    private function memberSignupIds(int $listId): array
    {
        $ids = [];
        foreach ($this->list($listId)->getSignUps() as $signup) {
            if ($signup instanceof ExternalSignup) {
                continue;
            }

            $ids[] = (int) $signup->getId();
        }

        return $ids;
    }

    /**
     * Create a self-service external sign-up and complete its double opt-in, making now its participation moment.
     */
    private function createConfirmedExternal(int $listId): ExternalSignup
    {
        $signupManager = self::getContainer()->get(SignupManager::class);
        $signup = $signupManager->createExternalSignup(
            $this->list($listId),
            'Late Confirmer',
            'late.confirmer@example.org',
            [],
        );

        $verification = $this->entityManager->getRepository(ExternalSignupVerification::class)->findOneBy([
            'externalSignup' => $signup,
            'purpose' => ExternalSignupVerificationPurpose::Verify,
        ]);
        self::assertInstanceOf(
            ExternalSignupVerification::class,
            $verification,
        );
        $signupManager->confirmExternalSignup($verification);

        return $signup;
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
     * Pin a seeded list's draw configuration and timing (and its activity's end, which bounds the admission window)
     * to explicit now-relative moments, directly in the database (rolled back with the test), so nothing depends on
     * seed freshness. Clears the entity manager afterwards so both the command and the assertions read the updated
     * rows.
     */
    private function reconfigure(
        int $listId,
        ?string $openDate = null,
        ?string $closeDate = null,
        ?string $endTime = null,
        ?AllocationMethod $method = null,
        ?DrawCutoffRule $rule = null,
        ?string $cutoffAt = null,
        ?int $durationHours = null,
        ?int $capacity = null,
    ): void {
        $revisionId = $this->list($listId)->getRevision()->getId();
        $connection = $this->entityManager->getConnection();

        $fields = [
            'openDate' => null === $openDate ? null : $this->sqlDateTime($openDate),
            'closeDate' => null === $closeDate ? null : $this->sqlDateTime($closeDate),
            'allocationMethod' => $method?->value,
            'drawCutoffRule' => $rule?->value,
            'drawCutoffAt' => null === $cutoffAt ? null : $this->sqlDateTime($cutoffAt),
            'drawAfterDurationHours' => $durationHours,
            'capacity' => $capacity,
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
