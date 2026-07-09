<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisionInterface;
use Override;

/**
 * Shared skeleton for the per-domain {@see RevisionClonerInterface} implementations. The lifecycle of spawning draft
 * N+1 is identical across activities, vacancies and companies (carry the authorship forward, increment the revision
 * number, link the new revision into the chain and attach it to its aggregate), and only the concrete revision type
 * and the content snapshot differ. Keeping that lifecycle here means a change to it (a new shared field, a different
 * default) is made once rather than in each cloner.
 *
 * Subclasses implement {@see spawnDraft()} (instantiate the concrete revision, set `previousRevision = source`, and
 * attach it to the aggregate as the current revision) and {@see copyContent()} (deep-copy the domain content: each
 * owned localised-text row recreated, never shared, or orphan-removal would delete it; shared associations re-linked).
 */
abstract readonly class AbstractRevisionCloner implements RevisionClonerInterface
{
    #[Override]
    public function cloneAsDraft(RevisionInterface $source): RevisionInterface
    {
        $draft = $this->spawnDraft($source);

        // Shared workflow wiring, carried forward for every domain. Authorship stays as the source's (a controller may
        // reassign it afterwards, e.g. when a different member edits an approved entity); the new revision is the next
        // in the chain and starts as a Draft (the default on AbstractRevision).
        $draft->setAuthor($source->getAuthor());
        $draft->setAuthorCompanyUser($source->getAuthorCompanyUser());
        $draft->setRevisionNumber($source->getRevisionNumber() + 1);

        $this->copyContent(
            $source,
            $draft,
        );

        return $draft;
    }

    /**
     * Instantiate the concrete draft revision from $source, link it as the chain's next revision
     * (`previousRevision = source`) and attach it to the aggregate as the current revision. The shared workflow wiring
     * (authorship, revision number) is filled in by {@see cloneAsDraft()} and the content by {@see copyContent()}.
     */
    abstract protected function spawnDraft(RevisionInterface $source): AbstractRevision;

    /**
     * Deep-copy the domain-specific content snapshot from $source into the freshly spawned $draft.
     */
    abstract protected function copyContent(
        RevisionInterface $source,
        AbstractRevision $draft,
    ): void;
}
