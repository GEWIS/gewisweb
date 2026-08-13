<?php

declare(strict_types=1);

namespace App\EventListener\Frontpage;

use App\Entity\Frontpage\PollRevision;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Keeps a poll from ever having something to edit.
 *
 *  - `request_changes` is withheld outright. It exists to hand a revision back to its author as a fresh draft, and a
 *    poll has no draft to hand back: the question was written and submitted in one go. Withholding it also keeps
 *    {@see \App\EventListener\Application\SpawnNextDraftListener} and the cloner registry out of a domain that has no
 *    cloner. The board says yes or no; a no is answered by asking again, which writes a new revision from scratch.
 *  - `submit` is withheld once the poll has a live revision. A question members have started answering cannot be
 *    replaced underneath them, so a second question is a second poll.
 *
 * Additive to the authorization guards in {@see \App\EventListener\Application\RevisionGuardListener}; all must pass.
 */
final readonly class PollRevisionGuardListener
{
    /**
     * @param GuardEvent<object> $event
     */
    #[AsEventListener(event: 'workflow.revision.guard.request_changes')]
    public function onRequestChanges(GuardEvent $event): void
    {
        if (!$event->getSubject() instanceof PollRevision) {
            return;
        }

        $event->setBlocked(
            true,
            'A poll cannot be sent back for changes; reject it and the question can be asked again.',
        );
    }

    /**
     * @param GuardEvent<object> $event
     */
    #[AsEventListener(event: 'workflow.revision.guard.submit')]
    public function onSubmit(GuardEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof PollRevision) {
            return;
        }

        if (null === $revision->getPoll()->getLiveRevision()) {
            return;
        }

        $event->setBlocked(
            true,
            'This poll has already been published; a new question has to be requested as a new poll.',
        );
    }
}
