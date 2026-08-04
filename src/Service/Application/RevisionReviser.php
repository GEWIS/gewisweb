<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\RevisionInterface;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Workflow\RevisionClonerRegistry;

/**
 * Starting a new draft off what a revisable currently shows, which is the only way anything already decided on is
 * changed. Whether that is allowed at all is {@see \App\Entity\Application\Enums\RevisionStatus::reviseRefusal()};
 * this is the part that does it, whichever kind of principal asked.
 *
 * The counterpart of {@see RevisionDiscarder}, which throws such a draft away again.
 */
final readonly class RevisionReviser
{
    public function __construct(private RevisionClonerRegistry $clonerRegistry)
    {
    }

    /**
     * A fresh draft off this revision, authored by whoever asked for it. The caller persists and flushes, so a draft
     * commits together with whatever else the same request did.
     */
    public function spawnDraft(
        RevisionInterface $revision,
        User|CompanyUser $author,
    ): RevisionInterface {
        $draft = $this->clonerRegistry->cloneAsDraft($revision);

        if ($author instanceof User) {
            $draft->setAuthor($author->getMember());
        } else {
            $draft->setAuthorCompanyUser($author);
        }

        return $draft;
    }
}
