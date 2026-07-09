<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Application;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Decision\Member;
use App\Entity\User\User;
use App\EventListener\Application\RevisionReviewStampListener;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

/**
 * Every board decision (approve / reject / request changes) must record who reviewed the revision and when, for
 * accountability. The reviewer is the deciding member; if there is somehow no member behind the token (e.g. a company
 * user), only the timestamp is recorded rather than crashing.
 */
final class RevisionReviewStampListenerTest extends TestCase
{
    public function testRecordsTheReviewerAndTimestampOnEveryBoardDecision(): void
    {
        foreach (
            [
                'approve',
                'reject',
                'request_changes',
            ] as $transition
        ) {
            $member = self::createStub(Member::class);
            $user = self::createStub(User::class);
            $user->method('getMember')->willReturn($member);
            $security = self::createStub(Security::class);
            $security->method('getUser')->willReturn($user);

            $revision = new ActivityRevision();
            $listener = new RevisionReviewStampListener($security);
            $listener->onTransition($this->transitionEvent($revision, $transition));

            self::assertSame(
                $member,
                $revision->getReviewer(),
                $transition . ' must record the deciding member as the reviewer',
            );
            self::assertNotNull($revision->getReviewedAt());
        }
    }

    public function testIgnoresNonReviewTransitions(): void
    {
        foreach (
            [
                'submit',
                'start_review',
                'close',
            ] as $transition
        ) {
            $member = self::createStub(Member::class);
            $user = self::createStub(User::class);
            $user->method('getMember')->willReturn($member);
            $security = self::createStub(Security::class);
            $security->method('getUser')->willReturn($user);

            $revision = new ActivityRevision();
            $listener = new RevisionReviewStampListener($security);
            $listener->onTransition($this->transitionEvent($revision, $transition));

            self::assertNull(
                $revision->getReviewer(),
                $transition . ' is not a board decision and must not stamp a reviewer',
            );
            self::assertNull($revision->getReviewedAt());
        }
    }

    public function testRecordsOnlyTheTimestampWhenThereIsNoMemberBehindTheToken(): void
    {
        $security = self::createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $revision = new ActivityRevision();
        $listener = new RevisionReviewStampListener($security);
        $listener->onTransition($this->transitionEvent($revision, 'approve'));

        self::assertNull($revision->getReviewer());
        self::assertNotNull($revision->getReviewedAt());
    }

    private function transitionEvent(
        object $subject,
        string $transition,
    ): TransitionEvent {
        return new TransitionEvent(
            $subject,
            new Marking([]),
            new Transition(
                $transition,
                'in-review',
                'approved',
            ),
        );
    }
}
