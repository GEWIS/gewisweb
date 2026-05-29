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

    #[AsEventListener(event: 'workflow.revision.guard.submit')]
    public function onSubmit(GuardEvent $event): void
    {
        $this->guard(
            $event,
            RevisionVoter::SUBMIT,
        );
    }

    #[AsEventListener(event: 'workflow.revision.guard.start_review')]
    public function onStartReview(GuardEvent $event): void
    {
        $this->guard(
            $event,
            RevisionVoter::APPROVE,
        );
    }

    #[AsEventListener(event: 'workflow.revision.guard.request_changes')]
    public function onRequestChanges(GuardEvent $event): void
    {
        $this->guard(
            $event,
            RevisionVoter::APPROVE,
        );
    }

    #[AsEventListener(event: 'workflow.revision.guard.reject')]
    public function onReject(GuardEvent $event): void
    {
        $this->guard(
            $event,
            RevisionVoter::APPROVE,
        );
    }

    #[AsEventListener(event: 'workflow.revision.guard.approve')]
    public function onApprove(GuardEvent $event): void
    {
        $this->guard(
            $event,
            RevisionVoter::APPROVE,
        );
    }

    #[AsEventListener(event: 'workflow.revision.guard.close')]
    public function onClose(GuardEvent $event): void
    {
        $this->guard(
            $event,
            RevisionVoter::APPROVE,
        );
    }

    private function guard(
        GuardEvent $event,
        string $attribute,
    ): void {
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
