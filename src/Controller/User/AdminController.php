<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\User\CompanyUserRepository;
use App\Repository\User\UserRepository;
use App\Service\User\SessionManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;

#[IsGranted(
    attribute: UserRoles::Admin->value,
    message: 'You are not allowed to administer users.',
)]
#[IsGranted('SUDO')]
#[Route(
    path: '/admin/users',
    name: 'admin/users/',
)]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CompanyUserRepository $companyUserRepository,
        private readonly SessionManager $sessionManager,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('user/admin/index.html.twig');
    }

    #[Route(
        path: '/company-users',
        name: 'company-users/index',
    )]
    public function companyUsersIndex(): Response
    {
        return $this->render('user/admin/company-users.html.twig');
    }

    #[Route(
        path: '/{lidnr}/sessions',
        name: 'sessions',
        requirements: ['lidnr' => '\d+'],
        methods: ['GET'],
    )]
    public function userSessions(int $lidnr): Response
    {
        $user = $this->requireUser($lidnr);

        return $this->render(
            'user/admin/sessions.html.twig',
            [
                'subjectLabel' => sprintf(
                    '%d',
                    $user->getLidnr(),
                ),
                'sessions' => $this->sessionManager->getActiveSessions(
                    $user,
                    'main',
                ),
                'backRoute' => 'admin/users/index',
                'terminateRoute' => 'admin/users/sessions/terminate',
                'terminateAllRoute' => 'admin/users/sessions/terminate-all',
                'routeParams' => ['lidnr' => $user->getLidnr()],
                'mfaEnabled' => $user->isTotpAuthenticationEnabled(),
            ],
        );
    }

    #[IsCsrfTokenValid(
        id: new Expression('"admin_session_terminate-" ~ args["series"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/{lidnr}/sessions/{series}/terminate',
        name: 'sessions/terminate',
        requirements: ['lidnr' => '\d+'],
        methods: ['POST'],
    )]
    public function terminateUserSession(
        int $lidnr,
        string $series,
        Request $request,
    ): Response {
        $user = $this->requireUser($lidnr);

        $success = $this->sessionManager->terminateSession(
            $user,
            $series,
            $request,
            'main',
        );

        $this->addFlash(
            $success ? AlertTypes::Success->value : AlertTypes::Warning->value,
            $this->translator->trans(
                $success
                    ? 'Session terminated.'
                    : 'Session not found or already expired.',
            ),
        );

        return $this->redirectToRoute(
            'admin/users/sessions',
            ['lidnr' => $lidnr],
        );
    }

    #[IsCsrfTokenValid(
        id: 'admin_session_terminate_all',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/{lidnr}/sessions/terminate-all',
        name: 'sessions/terminate-all',
        requirements: ['lidnr' => '\d+'],
        methods: ['POST'],
    )]
    public function terminateAllUserSessions(int $lidnr): Response
    {
        $user = $this->requireUser($lidnr);
        $count = $this->sessionManager->terminateAll(
            $user,
            'main',
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans(
                '%count% session(s) terminated.',
                ['%count%' => $count],
            ),
        );

        return $this->redirectToRoute(
            'admin/users/sessions',
            ['lidnr' => $lidnr],
        );
    }

    #[Route(
        path: '/company-users/{id}/sessions',
        name: 'company-users/sessions',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function companyUserSessions(int $id): Response
    {
        $companyUser = $this->requireCompanyUser($id);

        return $this->render(
            'user/admin/sessions.html.twig',
            [
                'subjectLabel' => $companyUser->getCompany()->getName(),
                'sessions' => $this->sessionManager->getActiveSessions(
                    $companyUser,
                    'company',
                ),
                'backRoute' => 'admin/users/company-users/index',
                'terminateRoute' => 'admin/users/company-users/sessions/terminate',
                'terminateAllRoute' => 'admin/users/company-users/sessions/terminate-all',
                'routeParams' => ['id' => $id],
                'mfaEnabled' => $companyUser->isTotpAuthenticationEnabled(),
            ],
        );
    }

    #[IsCsrfTokenValid(
        id: new Expression('"admin_session_terminate-" ~ args["series"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/company-users/{id}/sessions/{series}/terminate',
        name: 'company-users/sessions/terminate',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function terminateCompanyUserSession(
        int $id,
        string $series,
        Request $request,
    ): Response {
        $companyUser = $this->requireCompanyUser($id);

        $success = $this->sessionManager->terminateSession(
            $companyUser,
            $series,
            $request,
            'company',
        );

        $this->addFlash(
            $success ? AlertTypes::Success->value : AlertTypes::Warning->value,
            $this->translator->trans(
                $success
                    ? 'Session terminated.'
                    : 'Session not found or already expired.',
            ),
        );

        return $this->redirectToRoute(
            'admin/users/company-users/sessions',
            ['id' => $id],
        );
    }

    #[IsCsrfTokenValid(
        id: 'admin_session_terminate_all',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/company-users/{id}/sessions/terminate-all',
        name: 'company-users/sessions/terminate-all',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function terminateAllCompanyUserSessions(int $id): Response
    {
        $companyUser = $this->requireCompanyUser($id);
        $count = $this->sessionManager->terminateAll(
            $companyUser,
            'company',
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans(
                '%count% session(s) terminated.',
                ['%count%' => $count],
            ),
        );

        return $this->redirectToRoute(
            'admin/users/company-users/sessions',
            ['id' => $id],
        );
    }

    #[IsCsrfTokenValid(
        id: new Expression('"admin_mfa_reset-" ~ args["lidnr"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/{lidnr}/mfa/reset',
        name: 'mfa/reset',
        requirements: ['lidnr' => '\d+'],
        methods: ['POST'],
    )]
    public function resetUserMfa(int $lidnr): Response
    {
        $user = $this->requireUser($lidnr);

        if (!$user->isTotpAuthenticationEnabled()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Multi-factor authentication is not enabled for this user.'),
            );

            return $this->redirectToRoute('admin/users/index');
        }

        $user->setTotpSecret(null);
        $user->setBackupCodeSlots(null);
        $user->setForceReloginAt(new DateTime());
        $this->em->flush();

        $this->sessionManager->terminateAll(
            $user,
            'main',
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('MFA reset and all sessions terminated.'),
        );

        return $this->redirectToRoute('admin/users/index');
    }

    #[IsCsrfTokenValid(
        id: new Expression('"admin_mfa_reset-" ~ args["id"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/company-users/{id}/mfa/reset',
        name: 'company-users/mfa/reset',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function resetCompanyUserMfa(int $id): Response
    {
        $companyUser = $this->requireCompanyUser($id);

        if (!$companyUser->isTotpAuthenticationEnabled()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Multi-factor authentication is not enabled for this user.'),
            );

            return $this->redirectToRoute('admin/users/company-users/index');
        }

        $companyUser->setTotpSecret(null);
        $companyUser->setBackupCodeSlots(null);
        $companyUser->setForceReloginAt(new DateTime());
        $this->em->flush();

        $this->sessionManager->terminateAll(
            $companyUser,
            'company',
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('MFA reset and all sessions terminated.'),
        );

        return $this->redirectToRoute('admin/users/company-users/index');
    }

    private function requireUser(int $lidnr): User
    {
        $user = $this->userRepository->findOneBy(['lidnr' => $lidnr]);
        if (null === $user) {
            throw new NotFoundHttpException();
        }

        return $user;
    }

    private function requireCompanyUser(int $id): CompanyUser
    {
        $companyUser = $this->companyUserRepository->find($id);
        if (null === $companyUser) {
            throw new NotFoundHttpException();
        }

        return $companyUser;
    }
}
