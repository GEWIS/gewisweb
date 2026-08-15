<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Activity;

use App\Entity\Activity\ActivityProposal;
use App\EventListener\Activity\ActivityProposalGuardListener;
use App\Security\Activity\ActivityProposalVoter;
use App\Tests\Support\BuildsGuardEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

use function implode;

/**
 * Which attribute the option calendar's guard asks for, per transition.
 *
 * Every case here has somebody signed in, which is what a token stands for. With no token at all the guard steps
 * aside, because that only happens on the console: see the nightly sweep's own test.
 *
 * Only taking a proposal back is the body's own; deciding which date is reserved, turning a proposal down, recording
 * that the financial side is settled and letting a date lapse are all the board's. The guard asks the generic event
 * rather than one transition at a time, so a transition added later is locked to the board rather than open to
 * everybody, and that is what the last case pins.
 */
final class ActivityProposalGuardListenerTest extends TestCase
{
    use BuildsGuardEvents;

    public function testWithdrawIsAskedOfTheBody(): void
    {
        $this->assertAsksFor(
            ActivityProposalVoter::WITHDRAW,
            'withdraw',
        );
    }

    public function testSchedulingIsAskedOfTheBoard(): void
    {
        $this->assertAsksFor(
            ActivityProposalVoter::DECIDE,
            'schedule',
        );
    }

    public function testClearingTheBudgetIsAskedOfTheBoard(): void
    {
        $this->assertAsksFor(
            ActivityProposalVoter::DECIDE,
            'clear_budget',
        );
    }

    public function testATransitionNobodyThoughtOfIsAskedOfTheBoard(): void
    {
        $this->assertAsksFor(
            ActivityProposalVoter::DECIDE,
            'some_future_transition',
        );
    }

    public function testAnAllowedTransitionIsNotBlocked(): void
    {
        $proposal = new ActivityProposal();
        $event = $this->guardEvent(
            $proposal,
            'withdraw',
            'submitted',
            'withdrawn',
        );

        $security = self::createStub(Security::class);
        $security->method('getToken')->willReturn(new NullToken());
        $security->method('isGranted')->willReturn(true);

        new ActivityProposalGuardListener($security)->onGuard($event);

        self::assertFalse($event->isBlocked());
    }

    private function assertAsksFor(
        string $attribute,
        string $transition,
    ): void {
        $proposal = new ActivityProposal();
        $event = $this->guardEvent(
            $proposal,
            $transition,
            'submitted',
            'scheduled',
        );

        $security = $this->createMock(Security::class);
        $security->method('getToken')->willReturn(new NullToken());
        $security->expects(self::once())
            ->method('isGranted')
            ->with(
                $attribute,
                $proposal,
            )
            ->willReturn(false);

        new ActivityProposalGuardListener($security)->onGuard($event);

        self::assertTrue($event->isBlocked());
        self::assertStringContainsString(
            'not allowed',
            implode(
                ' ',
                $this->blockerMessages($event),
            ),
        );
    }
}
