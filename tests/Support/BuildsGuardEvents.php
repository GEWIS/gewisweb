<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

/**
 * Builds a real {@see GuardEvent} for unit-testing the `revision` workflow guard listeners in isolation. The listeners
 * only ever read {@see GuardEvent::getSubject()} and call {@see GuardEvent::setBlocked()}, so a minimal marking and
 * transition (no dispatched workflow) are enough to exercise them.
 */
trait BuildsGuardEvents
{
    private function guardEvent(
        object $subject,
        string $transition = 'approve',
        string $from = 'in-review',
        string $to = 'approved',
    ): GuardEvent {
        return new GuardEvent(
            $subject,
            new Marking([$from => 1]),
            new Transition(
                $transition,
                $from,
                $to,
            ),
        );
    }

    /**
     * The messages of every blocker the listener recorded on the event.
     *
     * @return string[]
     */
    private function blockerMessages(GuardEvent $event): array
    {
        $messages = [];
        foreach ($event->getTransitionBlockerList() as $blocker) {
            $messages[] = $blocker->getMessage();
        }

        return $messages;
    }
}
