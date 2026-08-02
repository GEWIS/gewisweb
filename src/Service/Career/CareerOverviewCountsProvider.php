<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Repository\Career\CompanyPackageRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use App\ViewModel\Career\Admin\OverviewCounts;

/**
 * The totals shown on the career overview's tab bar. All three tabs render that bar, so the counting lives here rather
 * than in each of the three actions.
 */
final readonly class CareerOverviewCountsProvider
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private CompanyPackageRepository $packageRepository,
        private VacancyRepository $vacancyRepository,
    ) {
    }

    public function counts(): OverviewCounts
    {
        return new OverviewCounts(
            companies: $this->companyRepository->count(),
            packages: $this->packageRepository->count(),
            vacancies: $this->vacancyRepository->count(),
        );
    }
}
