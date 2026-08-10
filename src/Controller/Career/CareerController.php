<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Career\Enums\VacancyCategories;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use App\Util\Application\SlugRule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_slice;
use function shuffle;

#[Route(
    path: '/career',
    name: 'career/',
)]
class CareerController extends AbstractController
{
    /**
     * The number of a company's upcoming activities shown on its detail page.
     */
    private const int COMPANY_ACTIVITY_LIMIT = 3;

    /**
     * How much of the landing page's taste of the vacancies is shown before it sends the reader on to the full list.
     */
    private const int LATEST_VACANCY_LIMIT = 4;

    /**
     * How many companies the landing page's strip shows before it sends the reader on to the full overview.
     */
    private const int COMPANY_STRIP_LIMIT = 9;

    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly VacancyRepository $vacancyRepository,
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    /**
     * Where somebody who is thinking about what comes after their degree lands: what GEWIS does about that, the events
     * that go with it, the company in the spotlight and the vacancies companies have put forward.
     */
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        $companies = $this->companyRepository->findAllPublic();

        // Randomise before slicing, so the strip is not a standing showcase for whoever is early in the alphabet.
        shuffle($companies);

        return $this->render(
            'career/index.html.twig',
            [
                'companies' => array_slice(
                    $companies,
                    0,
                    self::COMPANY_STRIP_LIMIT,
                ),
                'latestVacancies' => $this->vacancyRepository->findLatestForOverview(self::LATEST_VACANCY_LIMIT),
                'events' => $this->activityRepository->findUpcoming(category: ActivityCategories::Career),
            ],
        );
    }

    /**
     * Every company that is currently visible. The search, the random order and the paging are all handled by the
     * {@see \App\Twig\Components\Career\CompanyOverview} live component.
     */
    #[Route(
        path: '/companies',
        name: 'companies',
    )]
    public function companies(): Response
    {
        return $this->render('career/companies.html.twig');
    }

    /**
     * The public detail page of a single company: its full description, contact details and active vacancies.
     */
    #[Route(
        path: '/company/{slug}',
        name: 'company',
        requirements: ['slug' => SlugRule::ROUTE_REQUIREMENT],
    )]
    public function company(string $slug): Response
    {
        $company = $this->companyRepository->findPublicCompanyBySlugName($slug);

        if (null === $company) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'career/company.html.twig',
            [
                'company' => $company,
                'activities' => $this->activityRepository->findUpcomingByCompany(
                    $company,
                    self::COMPANY_ACTIVITY_LIMIT,
                ),
            ],
        );
    }

    /**
     * The public vacancies overview. All filtering (category, company, labels, search) is handled by the
     * {@see \App\Twig\Components\Career\VacancyOverview} live component, which mirrors its state into the query string;
     * a company card links here with `?category=...&company=...` pre-applied.
     */
    #[Route(
        path: '/vacancies',
        name: 'vacancies',
    )]
    public function vacancies(): Response
    {
        return $this->render('career/vacancies.html.twig');
    }

    /**
     * The public detail page of a single vacancy: its full description and the outward link to apply. Identified by the
     * owning company, its category and its slug (unique within that pair).
     */
    #[Route(
        path: '/company/{companySlug}/{category}/{vacancySlug}',
        name: 'vacancy',
        requirements: [
            'companySlug' => SlugRule::ROUTE_REQUIREMENT,
            'vacancySlug' => SlugRule::ROUTE_REQUIREMENT,
        ],
    )]
    public function vacancy(
        string $companySlug,
        string $category,
        string $vacancySlug,
    ): Response {
        $categoryEnum = VacancyCategories::tryFrom($category);

        if (null === $categoryEnum) {
            throw $this->createNotFoundException();
        }

        $vacancy = $this->vacancyRepository->findPublicVacancy(
            $companySlug,
            $categoryEnum,
            $vacancySlug,
        );

        if (null === $vacancy) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'career/vacancy.html.twig',
            ['vacancy' => $vacancy],
        );
    }
}
