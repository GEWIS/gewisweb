<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Career\VacancyRepository;
use App\Repository\User\CompanyUserRepository;
use App\ViewModel\Career\Portal\CompanyDashboard;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Where a representative lands after signing in: what their company's profile and vacancies are doing, what runs out
 * soon, and whether anything is waiting on them.
 */
#[IsGranted(
    attribute: UserRoles::Company->value,
    message: 'You are not allowed to view companies.',
)]
#[Route(
    path: '/company',
    name: 'company/',
)]
class CompanyController extends AbstractController
{
    /**
     * How far ahead the dashboard warns about a package running out. Long enough that a company can still do something
     * about it.
     */
    private const string HORIZON = 'P90D';

    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly CompanyUserRepository $companyUserRepository,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        return $this->render(
            'career/company/index.html.twig',
            [
                'dashboard' => CompanyDashboard::build(
                    $company,
                    $this->vacancyRepository->findAllForCompany($company),
                    $this->companyUserRepository->count(['company' => $company]),
                    new DateTime()->add(new DateInterval(self::HORIZON)),
                ),
            ],
        );
    }

    /**
     * Who else acts for the company. Read-only on purpose: inviting somebody, or shutting them out, is the committee's
     * call rather than the company's, so this only says who to ask.
     */
    #[Route(
        path: '/representatives',
        name: 'representatives',
    )]
    public function representatives(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        return $this->render(
            'career/company/representatives.html.twig',
            [
                'company' => $company,
                'representatives' => $this->companyUserRepository->findForCompany($company),
                'me' => $companyUser,
            ],
        );
    }
}
