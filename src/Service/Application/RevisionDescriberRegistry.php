<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\RevisionInterface;
use App\ViewModel\Application\Review\RevisionComparison;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

use function sprintf;

/**
 * Resolves the {@see RevisionDescriberInterface} for a given revision and delegates, so the review controllers stay
 * domain-agnostic.
 */
final readonly class RevisionDescriberRegistry
{
    /**
     * @param iterable<RevisionDescriberInterface> $describers
     */
    public function __construct(
        #[AutowireIterator('app.revision_describer')]
        private iterable $describers,
    ) {
    }

    public function describe(
        RevisionInterface $revision,
        ?RevisionInterface $previous,
    ): RevisionComparison {
        foreach ($this->describers as $describer) {
            if ($describer->supports($revision)) {
                return $describer->describe(
                    $revision,
                    $previous,
                );
            }
        }

        throw new RuntimeException(sprintf(
            'No revision describer supports "%s".',
            $revision::class,
        ));
    }
}
