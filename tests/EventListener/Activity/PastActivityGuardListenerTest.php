<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\EventListener\Activity\PastActivityGuardListener;
use App\Tests\Support\BuildsGuardEvents;
use DateTime;
use PHPUnit\Framework\TestCase;
use stdClass;

use function implode;

/**
 * An activity that has already happened must not be (re)published: a finished event can no longer be changed, and a
 * brand-new one whose start has passed could never be joined (its sign-up lists close before it begins). This guard
 * closes the loop for a revision still in flight when its deadline passes. The two cases are judged by different
 * deadlines -- an established activity by its live schedule's *end*, a brand-new one by its own *start* -- and each
 * raises its own message, which these tests pin. Far-past/far-future dates keep the assertions independent of the
 * real clock.
 */
final class PastActivityGuardListenerTest extends TestCase
{
    use BuildsGuardEvents;

    public function testBlocksSubmitWhenABrandNewActivityHasAlreadyStarted(): void
    {
        $revision = $this->brandNewRevision(new DateTime('2000-01-01 12:00'));

        $event = $this->guardEvent(
            $revision,
            'submit',
            'draft',
            'submitted',
        );
        $listener = new PastActivityGuardListener();
        $listener->onSubmit($event);

        self::assertTrue($event->isBlocked());
        self::assertStringContainsString(
            'already started',
            implode(
                "\n",
                $this->blockerMessages($event),
            ),
        );
    }

    public function testBlocksApproveWhenAnEstablishedActivityHasEnded(): void
    {
        $revision = $this->establishedInFlightRevision(new DateTime('2000-01-01 12:00'));

        $event = $this->guardEvent($revision);
        $listener = new PastActivityGuardListener();
        $listener->onApprove($event);

        self::assertTrue($event->isBlocked());
        self::assertStringContainsString(
            'already taken place',
            implode(
                "\n",
                $this->blockerMessages($event),
            ),
        );
    }

    public function testAllowsWhenTheRelevantDeadlineIsStillInTheFuture(): void
    {
        $brandNew = $this->brandNewRevision(new DateTime('2999-01-01 12:00'));
        $brandNewEvent = $this->guardEvent(
            $brandNew,
            'submit',
            'draft',
            'submitted',
        );
        $established = $this->establishedInFlightRevision(new DateTime('2999-01-01 12:00'));
        $establishedEvent = $this->guardEvent($established);

        $listener = new PastActivityGuardListener();
        $listener->onSubmit($brandNewEvent);
        $listener->onApprove($establishedEvent);

        self::assertFalse($brandNewEvent->isBlocked());
        self::assertFalse($establishedEvent->isBlocked());
    }

    public function testAllowsWhenTheRevisionHasNoSchedule(): void
    {
        $revision = $this->brandNewRevision(null);

        $event = $this->guardEvent(
            $revision,
            'submit',
            'draft',
            'submitted',
        );
        $listener = new PastActivityGuardListener();
        $listener->onSubmit($event);

        self::assertFalse($event->isBlocked());
    }

    public function testIgnoresNonActivityRevisions(): void
    {
        $event = $this->guardEvent(new stdClass());
        $listener = new PastActivityGuardListener();
        $listener->onApprove($event);

        self::assertFalse($event->isBlocked());
    }

    private function brandNewRevision(?DateTime $beginTime): ActivityRevision
    {
        $activity = new Activity();
        $revision = new ActivityRevision();
        $activity->addRevision($revision);
        $revision->setBeginTime($beginTime);

        return $revision;
    }

    /**
     * An in-flight revision on an activity that already has a (separate) live revision ending at the given time.
     */
    private function establishedInFlightRevision(?DateTime $liveEndTime): ActivityRevision
    {
        $activity = new Activity();

        $live = new ActivityRevision();
        $activity->addRevision($live);
        $live->setEndTime($liveEndTime);
        $activity->setLiveRevision($live);

        $inFlight = new ActivityRevision();
        $activity->addRevision($inFlight);

        return $inFlight;
    }
}
