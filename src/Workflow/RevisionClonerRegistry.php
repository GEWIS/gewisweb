<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Application\RevisionInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

use function sprintf;

/**
 * Resolves the {@see RevisionClonerInterface} for a given revision and delegates the clone, so callers (the workflow
 * listener that spawns draft N+1, the "edit an approved entity" controller flow) stay domain-agnostic.
 */
final readonly class RevisionClonerRegistry
{
    /**
     * @param iterable<RevisionClonerInterface> $cloners
     */
    public function __construct(
        #[AutowireIterator('app.revision_cloner')]
        private iterable $cloners,
    ) {
    }

    /**
     * Build the next draft revision (N+1) from {@see $source}, carrying its authorship forward.
     */
    public function cloneAsDraft(RevisionInterface $source): RevisionInterface
    {
        foreach ($this->cloners as $cloner) {
            if ($cloner->supports($source)) {
                return $cloner->cloneAsDraft($source);
            }
        }

        throw new RuntimeException(sprintf(
            'No revision cloner supports "%s".',
            $source::class,
        ));
    }
}
