<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Security\Activity\ActivityProposalVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Enforces who may drive each `activity_proposal` transition, by delegating to {@see ActivityProposalVoter}, so the
 * screens and the workflow cannot disagree about who may do what.
 *
 * `withdraw` is the body's own ({@see ActivityProposalVoter::WITHDRAW}); everything else is the board's. Guarding the
 * generic event rather than one transition at a time means a transition added later is locked to the board by default
 * rather than silently open to anyone.
 */
final readonly class ActivityProposalGuardListener
{
    public function __construct(private Security $security)
    {
    }

    /**
     * @param GuardEvent<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.guard')]
    public function onGuard(GuardEvent $event): void
    {
        $attribute = 'withdraw' === $event->getTransition()->getName()
            ? ActivityProposalVoter::WITHDRAW
            : ActivityProposalVoter::DECIDE;

        if (
            $this->security->isGranted(
                $attribute,
                $event->getSubject(),
            )
        ) {
            return;
        }

        $event->setBlocked(
            true,
            'You are not allowed to perform this transition.',
        );
    }
}
