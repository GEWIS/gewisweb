<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\RevisionInterface;
use App\Repository\Application\RevisionCommentRepositoryRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Throws away a draft and points its aggregate back at the version that is live. Works for any revisable domain: the
 * aggregate knows how to fall back and the registry knows where the thread lives, so nothing here has to know whether
 * it is looking at an activity, a company profile or a vacancy.
 *
 * Only ever used on a draft that has a live version behind it — discarding the very first draft would take the
 * aggregate with it, which is a deletion rather than a discard. The caller flushes, so an on-demand discard commits
 * with whatever else it undid and the stale-draft cleanup can keep batching its removals into one flush.
 */
final readonly class RevisionDiscarder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RevisionCommentRepositoryRegistry $comments,
        private EditLockService $editLocks,
    ) {
    }

    public function discardToLive(RevisionInterface $draft): void
    {
        $revisable = $draft->getRevisable();
        $revisable->restoreLiveRevision();

        // The draft is gone, so the edit lock on its aggregate means nothing: drop it here instead of leaving a
        // reviewer's discard to block the owner until the lock's TTL lapses. purge() only schedules the removal, so
        // it commits with the caller's flush.
        $this->editLocks->purge($revisable);

        $this->removeRevision($draft);
    }

    /**
     * Remove a revision together with its review comments, which reference it with a non-cascading foreign key.
     */
    public function removeRevision(RevisionInterface $revision): void
    {
        foreach ($this->comments->findForRevision($revision) as $comment) {
            $this->entityManager->remove($comment);
        }

        $this->entityManager->remove($revision);
    }
}
