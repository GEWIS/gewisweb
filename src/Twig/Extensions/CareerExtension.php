<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\Enums\VacancyCategories;
use App\Repository\Career\CompanyFeaturedPackageRepository;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CareerExtension extends AbstractExtension
{
    public function __construct(
        private readonly CompanyFeaturedPackageRepository $companyFeaturedPackageRepository,
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
                'vacancy_categories',
                $this->getVacancyCategories(...),
            ),
        ];
    }

    public function getFeaturedCompany(): ?CompanyFeaturedPackage
    {
        return $this->companyFeaturedPackageRepository->getFeaturedPackage();
    }

    /**
     * @return VacancyCategories[]
     */
    public function getVacancyCategories(): array
    {
        return VacancyCategories::cases();
    }
}
