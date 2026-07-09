<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Application\RevisionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Builds the next draft revision (N+1) from an existing revision, for a single revisable domain.
 *
 * Implementations deep-copy the source revision's content (each owned localised-text row is recreated, never shared,
 * or orphan-removal would delete it; shared associations such as labels are re-linked, never cloned), attach the new
 * revision to the same aggregate, set `revisionNumber = source + 1`, `previousRevision = source`, `status = Draft`,
 * and carry the source's authorship forward. The new revision is returned un-persisted; the caller persists and
 * flushes it (and may reassign the author, e.g. when a different user edits an approved entity).
 *
 * Concrete cloners are autoconfigured under the `app.revision_cloner` tag and resolved by
 * {@see RevisionClonerRegistry}.
 */
#[AutoconfigureTag('app.revision_cloner')]
interface RevisionClonerInterface
{
    /**
     * Whether this cloner handles the given revision's concrete type.
     */
    public function supports(RevisionInterface $revision): bool;

    /**
     * Create the next draft revision from {@see $source}, carrying its authorship forward.
     */
    public function cloneAsDraft(RevisionInterface $source): RevisionInterface;
}
