<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\CompanyUserInvite;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Career\CompanyRepresentativeInviteType;
use App\Repository\Career\CompanyRepository;
use App\Repository\User\CompanyUserInviteRepository;
use App\Repository\User\CompanyUserRepository;
use App\Repository\User\PasswordResetRepository;
use App\Security\User\Firewall;
use App\Service\Career\CompanyAuditLogger;
use App\Service\User\CompanyUserInviteService;
use App\Service\User\SessionManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Who may act for a company, from the board's side. Inviting somebody, shutting them out again and removing them
 * outright all live here; the portal only shows the company who its own people are.
 */
#[IsGranted(
    attribute: UserRoles::CompanyAdmin->value,
    message: 'You are not allowed to administer companies.',
)]
#[Route(
    path: '/admin/career/companies/{id}',
    name: 'admin/career/companies/',
    requirements: ['id' => '\d+'],
)]
class AdminRepresentativeController extends AbstractController
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly CompanyUserRepository $companyUserRepository,
        private readonly CompanyUserInviteRepository $inviteRepository,
        private readonly CompanyUserInviteService $inviteService,
        private readonly PasswordResetRepository $passwordResetRepository,
        private readonly SessionManager $sessionManager,
        private readonly CompanyAuditLogger $auditLogger,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/representatives',
        name: 'representatives',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function index(
        int $id,
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $company = $this->requireCompany($id);
        $form = $this->createForm(CompanyRepresentativeInviteType::class)->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            try {
                $this->inviteService->invite(
                    $company,
                    $form->get('email')->getData(),
                    $form->get('name')->getData(),
                    $user,
                );

                $this->addFlash(
                    AlertTypes::Success->value,
                    $this->translator->trans('The invitation has been sent.'),
                );

                return $this->redirectToRoute(
                    'admin/career/companies/representatives',
                    ['id' => $id],
                );
            } catch (RuntimeException $e) {
                $this->addFlash(
                    AlertTypes::Danger->value,
                    $this->translator->trans($e->getMessage()),
                );
            }
        }

        return $this->render(
            'career/admin/representatives.html.twig',
            [
                'company' => $company,
                'representatives' => $this->companyUserRepository->findForCompany($company),
                'invites' => $this->inviteRepository->findForCompany($company),
                'form' => $form,
            ],
        );
    }

    #[IsCsrfTokenValid(
        id: new Expression('"company_invite_resend-" ~ args["inviteId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/invites/{inviteId}/resend',
        name: 'invites/resend',
        requirements: ['inviteId' => '\d+'],
        methods: ['POST'],
    )]
    public function resendInvite(
        int $id,
        int $inviteId,
    ): Response {
        $this->inviteService->resend($this->requireInvite(
            $this->requireCompany($id),
            $inviteId,
        ));

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The invitation has been sent again.'),
        );

        return $this->backToIndex($id);
    }

    #[IsCsrfTokenValid(
        id: new Expression('"company_invite_revoke-" ~ args["inviteId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/invites/{inviteId}/revoke',
        name: 'invites/revoke',
        requirements: ['inviteId' => '\d+'],
        methods: ['POST'],
    )]
    public function revokeInvite(
        int $id,
        int $inviteId,
    ): Response {
        $this->inviteService->revoke($this->requireInvite(
            $this->requireCompany($id),
            $inviteId,
        ));

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The invitation has been withdrawn.'),
        );

        return $this->backToIndex($id);
    }

    /**
     * Shuts a representative out without erasing them: their sessions end now, and what they wrote stays readable.
     */
    #[IsCsrfTokenValid(
        id: new Expression('"company_representative_disable-" ~ args["representativeId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/representatives/{representativeId}/disable',
        name: 'representatives/disable',
        requirements: ['representativeId' => '\d+'],
        methods: ['POST'],
    )]
    public function disableRepresentative(
        int $id,
        int $representativeId,
        #[CurrentUser]
        User $user,
    ): Response {
        $company = $this->requireCompany($id);
        $representative = $this->requireRepresentative(
            $company,
            $representativeId,
        );

        if (!$representative->isDisabled()) {
            $representative->setDisabledAt(new DateTime());

            // Somebody who cannot sign in cannot be the contact either, so the company is left without one and the
            // board is asked to appoint a replacement.
            if ($company->getPrimaryContact() === $representative) {
                $company->setPrimaryContact(null);
            }

            $this->auditLogger->log(
                $company,
                $user,
                CompanyAuditVerbs::RepresentativeDisabled,
                $representative->getName(),
            );
            $this->entityManager->flush();

            $this->sessionManager->terminateAll(
                $representative,
                Firewall::Company->value,
            );
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The representative can no longer sign in.'),
        );

        return $this->backToIndex($id);
    }

    #[IsCsrfTokenValid(
        id: new Expression('"company_representative_enable-" ~ args["representativeId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/representatives/{representativeId}/enable',
        name: 'representatives/enable',
        requirements: ['representativeId' => '\d+'],
        methods: ['POST'],
    )]
    public function enableRepresentative(
        int $id,
        int $representativeId,
        #[CurrentUser]
        User $user,
    ): Response {
        $company = $this->requireCompany($id);
        $representative = $this->requireRepresentative(
            $company,
            $representativeId,
        );

        if ($representative->isDisabled()) {
            $representative->setDisabledAt(null);
            $this->auditLogger->log(
                $company,
                $user,
                CompanyAuditVerbs::RepresentativeEnabled,
                $representative->getName(),
            );
            $this->entityManager->flush();
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The representative can sign in again.'),
        );

        return $this->backToIndex($id);
    }

    /**
     * Removes the account outright. Everything that points at it from the review chains falls back to null, which is
     * why shutting somebody out is the milder option offered first.
     */
    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: new Expression('"company_representative_remove-" ~ args["representativeId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/representatives/{representativeId}/remove',
        name: 'representatives/remove',
        requirements: ['representativeId' => '\d+'],
        methods: ['POST'],
    )]
    public function removeRepresentative(
        int $id,
        int $representativeId,
        #[CurrentUser]
        User $user,
    ): Response {
        $company = $this->requireCompany($id);
        $representative = $this->requireRepresentative(
            $company,
            $representativeId,
        );

        $this->sessionManager->terminateAll(
            $representative,
            Firewall::Company->value,
        );
        $this->passwordResetRepository->deleteAllForCompanyUser($representative);

        $this->auditLogger->log(
            $company,
            $user,
            CompanyAuditVerbs::RepresentativeRemoved,
            $representative->getName(),
        );
        $this->entityManager->remove($representative);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The representative has been removed.'),
        );

        return $this->backToIndex($id);
    }

    #[IsCsrfTokenValid(
        id: new Expression('"company_representative_primary-" ~ args["representativeId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/representatives/{representativeId}/primary',
        name: 'representatives/primary',
        requirements: ['representativeId' => '\d+'],
        methods: ['POST'],
    )]
    public function makePrimaryContact(
        int $id,
        int $representativeId,
        #[CurrentUser]
        User $user,
    ): Response {
        $company = $this->requireCompany($id);
        $representative = $this->requireRepresentative(
            $company,
            $representativeId,
        );

        if ($representative->isDisabled()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Somebody who cannot sign in cannot be the primary contact.'),
            );

            return $this->backToIndex($id);
        }

        $company->setPrimaryContact($representative);
        $this->auditLogger->log(
            $company,
            $user,
            CompanyAuditVerbs::PrimaryContactChanged,
            $representative->getName(),
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The primary contact has been changed.'),
        );

        return $this->backToIndex($id);
    }

    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: new Expression('"company_representative_sessions-" ~ args["representativeId"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/representatives/{representativeId}/terminate-sessions',
        name: 'representatives/terminate-sessions',
        requirements: ['representativeId' => '\d+'],
        methods: ['POST'],
    )]
    public function terminateSessions(
        int $id,
        int $representativeId,
    ): Response {
        $count = $this->sessionManager->terminateAll(
            $this->requireRepresentative(
                $this->requireCompany($id),
                $representativeId,
            ),
            Firewall::Company->value,
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans(
                '%count% session(s) terminated.',
                ['%count%' => $count],
            ),
        );

        return $this->backToIndex($id);
    }

    private function backToIndex(int $id): Response
    {
        return $this->redirectToRoute(
            'admin/career/companies/representatives',
            ['id' => $id],
        );
    }

    private function requireCompany(int $id): Company
    {
        $company = $this->companyRepository->find($id);
        if (null === $company) {
            throw new NotFoundHttpException();
        }

        return $company;
    }

    private function requireRepresentative(
        Company $company,
        int $representativeId,
    ): CompanyUser {
        $representative = $this->companyUserRepository->find($representativeId);
        if (
            null === $representative
            || $representative->getCompany() !== $company
        ) {
            throw new NotFoundHttpException();
        }

        return $representative;
    }

    private function requireInvite(
        Company $company,
        int $inviteId,
    ): CompanyUserInvite {
        $invite = $this->inviteRepository->find($inviteId);
        if (
            null === $invite
            || $invite->getCompany() !== $company
        ) {
            throw new NotFoundHttpException();
        }

        return $invite;
    }
}
