<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Repository\Career\CompanyBannerPackageRepository;
use App\Repository\Career\CompanyFeaturedPackageRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use Override;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function array_slice;
use function array_sum;
use function shuffle;

/**
 * The navigation menu asks for the counts and the featured company on every page, and the career pages ask for the
 * same things again lower down, so both are answered once per request and remembered. The cache is cleared between
 * requests through {@see ResetInterface}, which matters under FrankenPHP's worker mode where this service outlives a
 * single request and would otherwise serve yesterday's numbers.
 */
class CareerExtension extends AbstractExtension implements ResetInterface
{
    /** @var array{categories: array<string, int>, vacancies: int, companies: int}|null */
    private ?array $menuCounts = null;

    private bool $featuredResolved = false;

    private ?CompanyFeaturedPackage $featured = null;

    public function __construct(
        private readonly CompanyBannerPackageRepository $companyBannerPackageRepository,
        private readonly CompanyFeaturedPackageRepository $companyFeaturedPackageRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly VacancyRepository $vacancyRepository,
    ) {
    }

    #[Override]
    public function reset(): void
    {
        $this->menuCounts = null;
        $this->featuredResolved = false;
        $this->featured = null;
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'company_banners',
                $this->getCompanyBanners(...),
            ),
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
            new TwigFunction(
                'career_menu_counts',
                $this->getMenuCounts(...),
            ),
        ];
    }

    /**
     * What the career menu puts beside its entries: how many vacancies are open in each category, the total across
     * them, and how many companies the overview lists.
     *
     * @return array{categories: array<string, int>, vacancies: int, companies: int}
     */
    public function getMenuCounts(): array
    {
        if (null !== $this->menuCounts) {
            return $this->menuCounts;
        }

        $categories = $this->vacancyRepository->countActiveByCategory();

        return $this->menuCounts = [
            'categories' => $categories,
            'vacancies' => array_sum($categories),
            'companies' => $this->companyRepository->countPublic(),
        ];
    }

    /**
     * The banners a page may put between its items right now, in the order they should be shown in.
     *
     * @return list<CompanyBannerPackage>
     */
    public function getCompanyBanners(): array
    {
        return $this->companyBannerPackageRepository->findActiveBanners();
    }

    public function getFeaturedCompany(): ?CompanyFeaturedPackage
    {
        if (!$this->featuredResolved) {
            $this->featured = $this->companyFeaturedPackageRepository->getFeaturedPackage();
            $this->featuredResolved = true;
        }

        return $this->featured;
    }

    /**
     * Everything companies with a running highlight package have chosen to put forward, and that is still showable.
     *
     * Shuffled, and cut down to $limit where one is given: the space was sold to each of these companies alike, so
     * neither the first slot nor making the cut at all may come down to the alphabet.
     *
     * @return Vacancy[]
     */
    public function getHighlightedVacancies(?int $limit = null): array
    {
        $vacancies = $this->vacancyRepository->findHighlighted();
        shuffle($vacancies);

        if (null === $limit) {
            return $vacancies;
        }

        return array_slice(
            $vacancies,
            0,
            $limit,
        );
    }

    /**
     * @return VacancyCategories[]
     */
    public function getVacancyCategories(): array
    {
        return VacancyCategories::cases();
    }
}
