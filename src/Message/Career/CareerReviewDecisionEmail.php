<?php

declare(strict_types=1);

namespace App\Message\Career;

use App\Entity\Application\Enums\RevisionStatus;

/**
 * Asynchronously tell a company's representatives what the committee decided. Carries the company and the outcome
 * rather than the revision, because by the time this is handled the workflow may already have spawned the next draft
 * and the revision it was about is no longer the one the company is looking at.
 */
class CareerReviewDecisionEmail
{
    public function __construct(
        private readonly int $companyId,
        private readonly RevisionStatus $outcome,
        private readonly string $subjectName,
        private readonly bool $isVacancy,
    ) {
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function getOutcome(): RevisionStatus
    {
        return $this->outcome;
    }

    public function getSubjectName(): string
    {
        return $this->subjectName;
    }

    public function isVacancy(): bool
    {
        return $this->isVacancy;
    }
}
