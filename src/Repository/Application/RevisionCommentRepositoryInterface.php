<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A repository that can find the review thread of one revision without the caller knowing which domain it is in. The
 * `revision` association is mapped on each concrete comment class rather than on the shared superclass, so the query
 * has to live with the mapping; this is what lets {@see \App\Service\Application\RevisionDiscarder} stay domain-blind.
 */
#[AutoconfigureTag('app.revision_comment_repository')]
interface RevisionCommentRepositoryInterface
{
    public function supports(RevisionInterface $revision): bool;

    /**
     * @return list<AbstractRevisionComment>
     */
    public function findForRevision(RevisionInterface $revision): array;
}
