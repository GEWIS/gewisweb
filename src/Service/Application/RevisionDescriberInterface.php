<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\RevisionInterface;
use App\ViewModel\Application\Review\RevisionComparison;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Says what a revision of one domain holds, so the review screens do not have to know. Adding a revisable domain is
 * then a field list rather than a template.
 */
#[AutoconfigureTag('app.revision_describer')]
interface RevisionDescriberInterface
{
    public function supports(RevisionInterface $revision): bool;

    /**
     * @param RevisionInterface|null $previous the revision this one is read against, absent for a first revision
     */
    public function describe(
        RevisionInterface $revision,
        ?RevisionInterface $previous,
    ): RevisionComparison;
}
