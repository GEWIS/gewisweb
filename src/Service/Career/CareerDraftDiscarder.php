<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Career\CompanyRevision;
use App\Entity\Career\VacancyRevision;
use App\Repository\Career\CompanyRevisionCommentRepository;
use App\Repository\Career\VacancyRevisionCommentRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Throws away a career draft and points its company or vacancy back at the version that is live. The caller flushes, so
 * a discard commits together with whatever else it needed to undo.
 *
 * Only ever used on a draft that has a live version behind it: discarding the very first draft would take the company
 * or vacancy with it, which is a deletion rather than a discard.
 */
final readonly class CareerDraftDiscarder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyRevisionCommentRepository $companyCommentRepository,
        private VacancyRevisionCommentRepository $vacancyCommentRepository,
    ) {
    }

    public function discardCompanyDraft(CompanyRevision $draft): void
    {
        $company = $draft->getCompany();
        $company->setCurrentRevision($company->getLiveRevision());

        foreach ($this->companyCommentRepository->findBy(['revision' => $draft]) as $comment) {
            $this->entityManager->remove($comment);
        }

        $this->entityManager->remove($draft);
    }

    public function discardVacancyDraft(VacancyRevision $draft): void
    {
        $vacancy = $draft->getVacancy();
        $vacancy->setCurrentRevision($vacancy->getLiveRevision());

        foreach ($this->vacancyCommentRepository->findBy(['revision' => $draft]) as $comment) {
            $this->entityManager->remove($comment);
        }

        $this->entityManager->remove($draft);
    }
}
