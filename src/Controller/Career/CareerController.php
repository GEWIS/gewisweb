<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Career\Enums\VacancyCategories;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly VacancyRepository $vacancyRepository,
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    /**
     * The public career landing page: an overview of all companies that are currently visible.
     */
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        $companies = $this->companyRepository->findAllPublic();

        // Randomise the order so no company is structurally favoured.
        shuffle($companies);

        return $this->render(
            'career/index.html.twig',
            ['companies' => $companies],
        );
    }

    /**
     * The public detail page of a single company: its full description, contact details and active vacancies.
     */
    #[Route(
        path: '/company/{slug}',
        name: 'company',
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
