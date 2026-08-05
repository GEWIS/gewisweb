<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisionInterface;
use App\ViewModel\Application\RevisionActions;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Answers what may be done with a revision, so the review screens, the decision form and the discard button all read
 * the same two predicates instead of each re-deriving them.
 */
final readonly class RevisionActionResolver
{
    public function __construct(
        #[Target('revisionStateMachine')]
        private WorkflowInterface $revisionStateMachine,
    ) {
    }

    public function resolve(RevisionInterface $revision): RevisionActions
    {
        $enabled = [];
        foreach ($this->revisionStateMachine->getEnabledTransitions($revision) as $transition) {
            $enabled[] = $transition->getName();
        }

        return new RevisionActions(
            enabledTransitions: $enabled,
            isResubmission: $this->isResubmission($revision),
            isDiscardable: $this->isDiscardable($revision),
        );
    }

    /**
     * A draft spawned because a reviewer asked for changes. Submitting it again has to say what was done about them,
     * which is what turns the decision form's message field into a required one.
     */
    private function isResubmission(RevisionInterface $revision): bool
    {
        return RevisionStatus::Draft === $revision->getStatus()
            && RevisionStatus::ChangesRequested === $revision->getPreviousRevision()?->getStatus();
    }

    /**
     * A draft of something that is already published can be thrown away, falling back to what is live. The very first
     * draft cannot: there is nothing behind it, so discarding it would be a deletion.
     */
    private function isDiscardable(RevisionInterface $revision): bool
    {
        $live = $revision->getRevisable()->getLiveRevision();

        return RevisionStatus::Draft === $revision->getStatus()
            && null !== $live
            && $live !== $revision;
    }
}
