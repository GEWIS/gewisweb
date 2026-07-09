<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Career\Company;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;

/**
 * A stable aggregate root whose content is versioned through a chain of {@see RevisionInterface}s.
 *
 * The aggregate keeps its identity (and anything that must survive across edits, e.g. an activity's sign-ups) while
 * each revision carries an immutable snapshot of the revisable content.
 *
 * The three resource accessors generalise the authorization hooks the legacy ACL used and are read by
 * {@see \App\Security\Application\RevisionVoter}.
 */
interface RevisableInterface
{
    /**
     * The aggregate's primary key, or null before it is persisted. All implementers key on an auto-increment id.
     */
    public function getId(): ?int;

    /**
     * A short, stable string identifying the resource type (e.g. 'activity', 'company', or 'vacancy').
     */
    public function getResourceId(): string;

    /**
     * The organ that owns/organises this resource, if any (used for organ-scoped edit rights).
     */
    public function getResourceOrgan(): ?Organ;

    /**
     * The member who created this resource, if any (null for company-owned resources such as vacancies).
     */
    public function getResourceCreator(): ?Member;

    /**
     * The company that owns this resource, if any (a vacancy/company profile); null for member/organ-owned resources
     * such as activities. Used to grant a company's users edit rights over their own resources.
     */
    public function getResourceCompany(): ?Company;

    /**
     * Every revision in the chain.
     *
     * @return iterable<RevisionInterface>
     */
    public function getRevisions(): iterable;

    /**
     * The most recent revision in the chain (highest revision number), regardless of state, or null when there is
     * none yet.
     */
    public function getCurrentRevision(): ?RevisionInterface;

    /**
     * The publicly live revision: the Approved revision with the highest revision number, or null when nothing has
     * been approved yet. A later non-approved revision never hides the current live one.
     */
    public function getLiveRevision(): ?RevisionInterface;

    /**
     * Promote the just-approved revision to be the publicly live one. Called by the workflow when a revision enters
     * the approved place; the just-approved revision is always the newest in the chain, so it supersedes any prior
     * live revision.
     */
    public function markRevisionLive(RevisionInterface $revision): void;
}
