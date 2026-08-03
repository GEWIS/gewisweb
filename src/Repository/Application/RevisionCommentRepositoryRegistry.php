<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

use function sprintf;

/**
 * Hands out the review thread of a revision, whichever domain it belongs to. Mirrors
 * {@see \App\Workflow\RevisionClonerRegistry}: a new revisable domain registers a repository and needs no change here.
 */
final readonly class RevisionCommentRepositoryRegistry
{
    /**
     * @param iterable<RevisionCommentRepositoryInterface> $repositories
     */
    public function __construct(
        #[AutowireIterator('app.revision_comment_repository')]
        private iterable $repositories,
    ) {
    }

    /**
     * @return list<AbstractRevisionComment>
     */
    public function findForRevision(RevisionInterface $revision): array
    {
        foreach ($this->repositories as $repository) {
            if (!$repository->supports($revision)) {
                continue;
            }

            return $repository->findForRevision($revision);
        }

        throw new RuntimeException(sprintf(
            'No comment repository supports revisions of "%s".',
            $revision::class,
        ));
    }
}
