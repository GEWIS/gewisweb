<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Security\Application\RevisionVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Enforces who may drive each `revision` workflow transition, by delegating to {@see RevisionVoter}. Keeping the
 * decision in the voter means that `#[IsGranted(...)]` checks in controllers/templates and the workflow agree.
 *
 * `submit` is author-side ({@see RevisionVoter::SUBMIT}); the board-side transitions (`start_review`,
 * `request_changes`, `reject`, `approve`, `close`) all require {@see RevisionVoter::APPROVE}.
 */
final readonly class RevisionGuardListener
{
    public function __construct(
        private Security $security,
    ) {
    }

    /**
     * One guard for every `revision` transition (the generic guard event fires for all): `submit` is author-side
     * ({@see RevisionVoter::SUBMIT}); every other transition (`start_review`, `request_changes`, `reject`, `approve`,
     * `close`, and any future board-side transition) requires {@see RevisionVoter::APPROVE}. Guarding generically means
     * a newly added transition is fail-safe (locked to the board) by default rather than silently unguarded.
     */
    #[AsEventListener(event: 'workflow.revision.guard')]
    public function onGuard(GuardEvent $event): void
    {
        $attribute = 'submit' === $event->getTransition()->getName()
            ? RevisionVoter::SUBMIT
            : RevisionVoter::APPROVE;

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
