<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\RevisionInterface;
use App\ViewModel\Application\Review\RevisionComparison;
use App\ViewModel\Application\Review\RevisionSection;
use Override;

/**
 * What every describer does before it can say anything: recognise its own revisions, and refuse to read one against a
 * predecessor of another kind, which would compare a company profile with a vacancy.
 *
 * A domain is then only its field list, which is the point of describing a revision rather than writing out a screen
 * for it.
 */
abstract class AbstractRevisionDescriber implements RevisionDescriberInterface
{
    use BuildsRevisionFieldsTrait;

    /**
     * The revision class this describer reads.
     *
     * @return class-string<RevisionInterface>
     */
    abstract protected function revisionClass(): string;

    /**
     * What this domain shows, for a revision already known to be its own.
     *
     * @param RevisionInterface|null $previous   the revision this one is read against, null when there is none of the
     *                                           right kind
     * @param bool                   $comparable whether there is anything to compare against at all, which every
     *                                           field needs to know
     *
     * @return list<RevisionSection>
     */
    abstract protected function sections(
        RevisionInterface $revision,
        ?RevisionInterface $previous,
        bool $comparable,
    ): array;

    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        $class = $this->revisionClass();

        return $revision instanceof $class;
    }

    #[Override]
    public function describe(
        RevisionInterface $revision,
        ?RevisionInterface $previous,
    ): RevisionComparison {
        if (!$this->supports($revision)) {
            return new RevisionComparison([]);
        }

        if (
            null !== $previous
            && !$this->supports($previous)
        ) {
            $previous = null;
        }

        return new RevisionComparison($this->sections(
            $revision,
            $previous,
            null !== $previous,
        ));
    }
}
