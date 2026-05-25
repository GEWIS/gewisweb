<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\JobCategory;
use App\Repository\Career\CompanyFeaturedPackageRepository;
use App\Repository\Career\JobCategoryRepository;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CareerExtension extends AbstractExtension
{
    public function __construct(
        private readonly CompanyFeaturedPackageRepository $companyFeaturedPackageRepository,
        private readonly JobCategoryRepository $jobCategoryRepository,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'featured_company',
                $this->getFeaturedCompany(...),
            ),
            new TwigFunction(
                'job_categories',
                $this->getJobCategories(...),
            ),
        ];
    }

    public function getFeaturedCompany(): ?CompanyFeaturedPackage
    {
        return $this->companyFeaturedPackageRepository->getFeaturedPackage();
    }

    /**
     * @return JobCategory[]
     */
    public function getJobCategories(): array
    {
        return $this->jobCategoryRepository->findVisibleCategories();
    }
}
