<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Application;

use App\EventListener\Application\RevisionGuardListener;
use App\Security\Application\RevisionVoter;
use App\Tests\Support\BuildsGuardEvents;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * This listener is the single bridge that keeps the workflow's transition guards and the `#[IsGranted(...)]` checks in
 * controllers/templates in agreement: each transition must delegate to the right {@see RevisionVoter} attribute, and a
 * denied vote must block the transition. A wrong mapping would silently let an unauthorised user drive a transition,
 * so these tests pin both the attribute per transition and the block-on-deny behaviour.
 */
final class RevisionGuardListenerTest extends TestCase
{
    use BuildsGuardEvents;

    public function testSubmitIsAuthorisedThroughTheSubmitAttribute(): void
    {
        $subject = new stdClass();
        $security = self::createMock(Security::class);
        $security->expects(self::once())
            ->method('isGranted')
            ->with(
                RevisionVoter::SUBMIT,
                $subject,
            )
            ->willReturn(true);

        $event = $this->guardEvent(
            $subject,
            'submit',
            'draft',
            'submitted',
        );
        $listener = new RevisionGuardListener($security);
        $listener->onGuard($event);

        self::assertFalse($event->isBlocked());
    }

    public function testEveryBoardTransitionIsAuthorisedThroughTheApproveAttribute(): void
    {
        $boardTransitions = [
            'start_review',
            'request_changes',
            'reject',
            'approve',
            'close',
        ];

        foreach ($boardTransitions as $transition) {
            $subject = new stdClass();
            $security = self::createMock(Security::class);
            $security->expects(self::once())
                ->method('isGranted')
                ->with(
                    RevisionVoter::APPROVE,
                    $subject,
                )
                ->willReturn(false);

            $event = $this->guardEvent(
                $subject,
                $transition,
            );
            $listener = new RevisionGuardListener($security);
            $listener->onGuard($event);

            self::assertTrue(
                $event->isBlocked(),
                $transition . ' must be authorised through the APPROVE attribute',
            );
        }
    }

    public function testBlocksWithAClearMessageWhenTheVoteIsDenied(): void
    {
        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturn(false);

        $event = $this->guardEvent(new stdClass());
        $listener = new RevisionGuardListener($security);
        $listener->onGuard($event);

        self::assertTrue($event->isBlocked());
        self::assertContains(
            'You are not allowed to perform this transition.',
            $this->blockerMessages($event),
        );
    }
}
