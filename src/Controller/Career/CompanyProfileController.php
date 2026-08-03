<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Controller\Application\AbstractRevisionReviewController;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Form\Career\CompanyType;
use App\Repository\Career\CompanyAuditLogRepository;
use App\Repository\Career\CompanyRevisionCommentRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\EditLockService;
use App\Service\Career\CompanyAuditLogger;
use App\Service\Career\CompanyImageUploadService;
use App\ViewModel\Application\RevisionActions;
use App\Workflow\RevisionClonerRegistry;
use Override;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function assert;

/**
 * A company's own profile: what is live, what it is working on, and how a proposal is getting on with the committee.
 *
 * A company never edits what is public. It works on a draft, submits it, and the committee decides; until then the
 * approved version is what visitors see. Every action here resolves the company from the signed-in representative
 * rather than from the URL, so there is nothing to point at somebody else's.
 */
#[IsGranted(
    attribute: UserRoles::Company->value,
    message: 'You are not allowed to view companies.',
)]
#[Route(
    path: '/company/profile',
    name: 'company/',
)]
class CompanyProfileController extends AbstractRevisionReviewController
{
    public function __construct(
        private readonly CompanyRevisionCommentRepository $commentRepository,
        private readonly CompanyAuditLogRepository $auditLogRepository,
        private readonly CompanyAuditLogger $auditLogger,
        private readonly CompanyImageUploadService $imageUploadService,
        private readonly EditLockService $editLockService,
        private readonly RevisionClonerRegistry $clonerRegistry,
    ) {
    }

    #[Route(
        path: '',
        name: 'profile',
        methods: ['GET'],
    )]
    public function view(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        return $this->render(
            'career/company/profile.html.twig',
            [
                'company' => $company,
                'timeline' => $this->auditLogRepository->findRecentForCompany($company),
            ],
        );
    }

    #[Route(
        path: '/edit',
        name: 'profile/edit',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $company,
        );

        $current = $company->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        if (!$current->getStatus()->isEditableByAuthor()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Your profile is not a draft right now. Revise it to start a new one.'),
            );

            return $this->redirectToRoute('company/profile');
        }

        if (
            null === $this->editLockService->acquire(
                $company,
                $companyUser,
            )
        ) {
            return $this->render(
                'career/company/edit-locked.html.twig',
                [
                    'lock' => $this->editLockService->blockingLock(
                        $company,
                        $companyUser,
                    ),
                    'backRoute' => 'company/profile',
                    'subject' => $this->translator->trans('your profile'),
                ],
            );
        }

        $form = $this->createForm(
            CompanyType::class,
            $company,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/company/profile-edit.html.twig',
                [
                    'form' => $form,
                    'company' => $company,
                    'comments' => $this->commentRepository->findThreadForCompany($company),
                ],
            );
        }

        $file = $form->get('currentRevision')->get('logoFile')->getData();
        if ($file instanceof UploadedFile) {
            $path = $this->imageUploadService->uploadLogo(
                $company,
                $file,
            );

            if (null === $path) {
                $this->addFlash(
                    AlertTypes::Warning->value,
                    $this->translator->trans('The logo could not be stored, so the previous one is still in use.'),
                );
            } else {
                $current->setLogo($path);
                $this->auditLogger->log(
                    $company,
                    $companyUser,
                    CompanyAuditVerbs::LogoUploaded,
                );
            }
        }

        $current->setLastEditedByCompanyUser($companyUser);
        $this->entityManager->flush();
        $this->editLockService->release(
            $company,
            $companyUser,
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your changes are saved. Submit them for review when you are ready.'),
        );

        return $this->redirectToRoute('company/profile/status');
    }

    #[Route(
        path: '/edit/ping',
        name: 'profile/edit_ping',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'company_profile_edit_lock',
        tokenKey: '_csrf_token',
    )]
    public function editPing(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): JsonResponse {
        $company = $companyUser->getCompany();
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $company,
        );

        return new JsonResponse([
            'held' => $this->editLockService->ping(
                $company,
                $companyUser,
            ),
        ]);
    }

    #[Route(
        path: '/edit/release',
        name: 'profile/edit_release',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'company_profile_edit_lock',
        tokenKey: '_csrf_token',
    )]
    public function editRelease(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): JsonResponse {
        $company = $companyUser->getCompany();
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $company,
        );

        $this->editLockService->release(
            $company,
            $companyUser,
        );

        return new JsonResponse(['released' => true]);
    }

    #[Route(
        path: '/revise',
        name: 'profile/revise',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'company_profile_revise',
        tokenKey: '_csrf_token',
    )]
    public function revise(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $company,
        );

        $current = $company->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        if ($current->getStatus()->isEditableByAuthor()) {
            return $this->redirectToRoute('company/profile/edit');
        }

        if (RevisionStatus::Closed === $current->getStatus()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Your profile was closed. Get in touch with the committee to reopen it.'),
            );

            return $this->redirectToRoute('company/profile');
        }

        // The registry is typed to the shared revision contract; for a company it always yields a CompanyRevision.
        $draft = $this->clonerRegistry->cloneAsDraft($current);
        if (!$draft instanceof CompanyRevision) {
            throw $this->createNotFoundException();
        }

        $draft->setAuthorCompanyUser($companyUser);
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $this->redirectToRoute('company/profile/edit');
    }

    /**
     * How the working version is getting on: what it says, what the committee said back, and whatever the company can
     * do about it right now.
     */
    #[Route(
        path: '/status',
        name: 'profile/status',
        methods: ['GET'],
    )]
    public function status(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        return $this->renderStatus(
            $company,
            $this->createDecisionForm($this->revisionActions($this->requireCurrentRevision($company))),
        );
    }

    #[Route(
        path: '/decide',
        name: 'profile/decide',
        methods: ['POST'],
    )]
    public function decide(
        Request $request,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();
        $current = $this->requireCurrentRevision($company);

        $form = $this->createDecisionForm($this->revisionActions($this->requireCurrentRevision($company)))
            ->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderStatus(
                $company,
                $form,
            );
        }

        if (
            null === $this->applyDecision(
                $form,
                $current,
                $companyUser,
            )
        ) {
            return $this->reviewResponse($current);
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your profile was submitted for review.'),
        );

        return $this->redirectToRoute('company/profile/status');
    }

    #[Route(
        path: '/comment',
        name: 'profile/comment',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'company_profile_comment',
        tokenKey: '_csrf_token',
    )]
    public function comment(
        Request $request,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();
        $current = $this->requireCurrentRevision($company);

        $this->handleCommentPost(
            $request,
            $current,
            $companyUser,
        );

        return $this->reviewResponse($current);
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function renderStatus(
        Company $company,
        ?FormInterface $form = null,
    ): Response {
        return $this->renderReview(
            $this->requireCurrentRevision($company),
            $form,
        );
    }

    #[Override]
    protected function reviewTemplate(): string
    {
        return 'career/company/profile-status.html.twig';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function reviewContext(
        RevisionInterface $revision,
        RevisionActions $actions,
    ): array {
        assert($revision instanceof CompanyRevision);
        $company = $revision->getCompany();

        return [
            'company' => $company,
            'comments' => $this->commentRepository->findThreadForCompany($company),
        ];
    }

    #[Override]
    protected function reviewResponse(RevisionInterface $revision): Response
    {
        return $this->redirectToRoute('company/profile/status');
    }

    private function requireCurrentRevision(Company $company): CompanyRevision
    {
        $current = $company->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        return $current;
    }
}
