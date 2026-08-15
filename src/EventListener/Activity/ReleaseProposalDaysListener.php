<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\DateOptionStatus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\Event;

/**
 * Frees the days a proposal was standing on once it is out of the running, and keeps the budget stamp honest.
 *
 * A day nobody is holding has to be free the moment they stop holding it, or the calendar quietly fills with claims
 * that no longer mean anything, which is what the old overdue email existed to chase by hand.
 *
 * An activity that was already started for the body is deliberately left where it is. If nobody ever touches it,
 * {@see \App\Command\Activity\DeleteStaleDraftsCommand} reaps it after a month, which is its whole job and is a good
 * deal more careful about it than a workflow listener could be; and if somebody did touch it, it is their work and
 * losing a day is no reason to throw it away. The proposal keeps pointing at it either way, and the association is
 * `SET NULL` on delete, so the record of who held the day survives the reaping.
 */
final readonly class ReleaseProposalDaysListener
{
    /**
     * @param Event<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.entered.withdrawn')]
    public function onWithdrawn(Event $event): void
    {
        $this->release(
            $event,
            DateOptionStatus::Withdrawn,
        );
    }

    /**
     * @param Event<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.entered.lapsed')]
    public function onLapsed(Event $event): void
    {
        $this->release(
            $event,
            DateOptionStatus::Declined,
        );
    }

    /**
     * @param Event<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.entered.declined')]
    public function onDeclined(Event $event): void
    {
        $this->release(
            $event,
            DateOptionStatus::Declined,
        );
    }

    /**
     * Reaching `scheduled` means the financial side is not settled: either it never was, or the board has just taken a
     * clearance back. Either way the stamp goes and the reminder is armed again.
     *
     * @param Event<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.entered.scheduled')]
    public function onScheduled(Event $event): void
    {
        $proposal = $event->getSubject();

        if (!$proposal instanceof ActivityProposal) {
            return;
        }

        $proposal->setBudgetClearance(null);
        $proposal->setBudgetClearedBy(null);
        $proposal->setBudgetClearedAt(null);
        $proposal->setBudgetRemindedAt(null);
    }

    /**
     * @param Event<object> $event
     */
    private function release(
        Event $event,
        DateOptionStatus $status,
    ): void {
        $proposal = $event->getSubject();

        if (!$proposal instanceof ActivityProposal) {
            return;
        }

        foreach ($proposal->getDateOptions() as $dateOption) {
            if (!$dateOption->getStatus()->isStanding()) {
                continue;
            }

            $dateOption->setStatus($status);
        }
    }
}
