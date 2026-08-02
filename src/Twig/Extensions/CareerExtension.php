<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Repository\Career\CompanyFeaturedPackageRepository;
use App\Repository\Career\VacancyRepository;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CareerExtension extends AbstractExtension
{
    public function __construct(
        private readonly CompanyFeaturedPackageRepository $companyFeaturedPackageRepository,
        private readonly VacancyRepository $vacancyRepository,
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
                'highlighted_vacancies',
                $this->getHighlightedVacancies(...),
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
     * Everything companies with a running highlight package have chosen to put forward, and that is still showable.
     *
     * @return Vacancy[]
     */
    public function getHighlightedVacancies(): array
    {
        return $this->vacancyRepository->findHighlighted();
    }

    /**
     * @return VacancyCategories[]
     */
    public function getVacancyCategories(): array
    {
        return VacancyCategories::cases();
    }
}
