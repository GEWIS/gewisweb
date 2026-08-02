<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\Enums\NotificationType;
use App\Repository\Career\CompanyBannerPackageRepository;
use App\Repository\Career\CompanyRevisionRepository;
use App\Repository\Career\VacancyRevisionRepository;
use App\Service\Application\AbstractNotificationSubjectNamer;
use Override;

/**
 * A vacancy under review reads by its own title; a company profile and a proposed banner have no name of their own, so
 * both read by the company that put them forward.
 */
final class CareerNotificationSubjectNamer extends AbstractNotificationSubjectNamer
{
    public function __construct(
        private readonly CompanyRevisionRepository $companyRevisionRepository,
        private readonly VacancyRevisionRepository $vacancyRevisionRepository,
        private readonly CompanyBannerPackageRepository $bannerPackageRepository,
    ) {
    }

    #[Override]
    public function supports(NotificationType $type): bool
    {
        return NotificationType::CompanyRevisionAwaitingReview === $type
            || NotificationType::VacancyRevisionAwaitingReview === $type
            || NotificationType::CompanyBannerAwaitingReview === $type;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function namesFor(
        NotificationType $type,
        array $subjectIds,
    ): array {
        $names = [];

        if (NotificationType::VacancyRevisionAwaitingReview === $type) {
            foreach ($this->vacancyRevisionRepository->findBy(['id' => $subjectIds]) as $revision) {
                $id = $revision->getId();
                if (null === $id) {
                    continue;
                }

                $names[$id] = $this->localised($revision->getName());
            }

            return $names;
        }

        $subjects = NotificationType::CompanyRevisionAwaitingReview === $type
            ? $this->companyRevisionRepository->findBy(['id' => $subjectIds])
            : $this->bannerPackageRepository->findBy(['id' => $subjectIds]);

        foreach ($subjects as $subject) {
            $id = $subject->getId();
            if (null === $id) {
                continue;
            }

            $names[$id] = $this->plain($subject->getCompany()->getName());
        }

        return $names;
    }
}
