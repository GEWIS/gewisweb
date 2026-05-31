<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Repository\Activity\ActivityRevisionCommentRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Discards an activity draft: points the activity back at its live (approved) revision and deletes the draft together
 * with its dependent rows. Shared by the on-demand "discard draft" action
 * ({@see \App\Controller\Activity\AdminApprovalController::discard()}) and the stale-draft cleanup
 * ({@see \App\Command\Activity\DeleteStaleDraftsCommand}); the caller is responsible for flushing, so the cleanup can
 * keep batching its removals into a single flush.
 */
final readonly class DraftDiscarder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActivityRevisionCommentRepository $commentRepository,
    ) {
    }

    /**
     * Revert the activity to its live revision and remove the given draft. The caller must ensure the activity has a
     * live revision to fall back to (a never-approved draft has nothing to revert to).
     */
    public function discardToLive(ActivityRevision $draft): void
    {
        $activity = $draft->getActivity();
        $activity->setCurrentRevision($activity->getLiveRevision());

        $this->removeRevision($draft);
    }

    /**
     * Remove a revision together with its review comments, which reference it with a non-cascading foreign key.
     */
    public function removeRevision(ActivityRevision $revision): void
    {
        foreach ($this->commentRepository->findBy(['revision' => $revision]) as $comment) {
            $this->entityManager->remove($comment);
        }

        $this->entityManager->remove($revision);
    }
}
