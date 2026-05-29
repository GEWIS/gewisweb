<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use DateTime;

/**
 * A single, self-contained revision in a revision chain. Once submitted, a revision is an immutable record; further
 * change happens by spawning a new revision linked to this one (see {@see getPreviousRevision()}).
 *
 * Implemented by {@see AbstractRevision}; the concrete subclasses (`ActivityRevision`, `VacancyRevision`, ...) add the
 * domain-specific content and the typed back-reference to their {@see RevisableInterface} aggregate (`Activity`, ...).
 */
interface RevisionInterface
{
    public function getId(): ?int;

    public function getStatus(): RevisionStatus;

    /**
     * Set the lifecycle state. Intended ONLY for the Symfony Workflow marking store
     * ({@see \App\Workflow\RevisionStatusMarkingStore}) and for seeding fixtures.
     *
     * NOTE: application code transitions a revision through `$workflow->apply()`, never by calling this directly.
     */
    public function setStatus(RevisionStatus $status): void;

    /**
     * The 1-based position of this revision in its chain.
     */
    public function getRevisionNumber(): int;

    public function setRevisionNumber(int $revisionNumber): void;

    /**
     * The revision this one supersedes, or null for the first revision in a chain.
     */
    public function getPreviousRevision(): ?RevisionInterface;

    /**
     * The member who authored this revision, if it was authored by a member (null when authored by a company).
     */
    public function getAuthor(): ?Member;

    public function setAuthor(?Member $author): void;

    /**
     * The company user who authored this revision, if it was authored by a company (null when authored by a member).
     */
    public function getAuthorCompanyUser(): ?CompanyUser;

    /**
     * A human-readable name for whoever authored this revision.
     */
    public function getAuthorDisplayName(): string;

    /**
     * The member who last reviewed (approved/rejected/requested changes on) this revision, if any.
     */
    public function getReviewer(): ?Member;

    public function setReviewer(?Member $reviewer): void;

    public function getReviewedAt(): ?DateTime;

    public function setReviewedAt(?DateTime $reviewedAt): void;

    /**
     * The stable aggregate this revision belongs to.
     */
    public function getRevisable(): RevisableInterface;
}
