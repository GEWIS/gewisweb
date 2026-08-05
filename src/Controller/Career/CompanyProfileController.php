<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Controller\Application\AbstractRevisionReviewController;
use App\Controller\Application\HoldsEditLockTrait;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\ReviseRefusal;
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
use App\Service\Application\RevisionDiscarder;
use App\Service\Application\RevisionReviser;
use App\Service\Career\CompanyAuditLogger;
use App\Service\Career\CompanyImageUploadService;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\RevisionActions;
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
    use HoldsEditLockTrait;

    public function __construct(
        private readonly CompanyRevisionCommentRepository $commentRepository,
        private readonly CompanyAuditLogRepository $auditLogRepository,
        private readonly CompanyAuditLogger $auditLogger,
        private readonly CompanyImageUploadService $imageUploadService,
        private readonly RevisionReviser $reviser,
        private readonly RevisionDiscarder $draftDiscarder,
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
        return $this->pingLock(
            $companyUser->getCompany(),
            $companyUser,
        );
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
        return $this->releaseLock(
            $companyUser->getCompany(),
            $companyUser,
        );
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

        $refusal = $current->getStatus()->reviseRefusal();

        // A draft that is already there is what the representative wants to work on, which is not worth a warning.
        if (ReviseRefusal::AlreadyADraft === $refusal) {
            return $this->redirectToRoute('company/profile/edit');
        }

        if (ReviseRefusal::UnderReview === $refusal) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans(
                    'Your profile is with the committee. Wait for their decision before revising it again.',
                ),
            );

            return $this->redirectToRoute('company/profile/status');
        }

        if (ReviseRefusal::Closed === $refusal) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Your profile was closed. Get in touch with the committee to reopen it.'),
            );

            return $this->redirectToRoute('company/profile');
        }

        $draft = $this->reviser->spawnDraft(
            $current,
            $companyUser,
        );
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
        return $this->handleDecision(
            $request,
            $this->requireCurrentRevision($companyUser->getCompany()),
            $companyUser,
        );
    }

    /**
     * Throw away a draft and go back to what is live, for when a change turns out not to be worth making. Without it
     * the only way out of a draft nobody wants is to submit it anyway.
     */
    #[Route(
        path: '/discard',
        name: 'profile/discard',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'company_profile_discard',
        tokenKey: '_csrf_token',
    )]
    public function discard(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();
        $current = $this->requireCurrentRevision($company);

        $this->denyAccessUnlessGranted(
            RevisionVoter::EDIT,
            $current,
        );

        if (!$this->revisionActions($current)->isDiscardable) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This draft cannot be discarded.'),
            );

            return $this->redirectToRoute('company/profile/status');
        }

        $this->draftDiscarder->discardToLive($current);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The draft was discarded and your live profile restored.'),
        );

        return $this->redirectToRoute('company/profile');
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
     * @param FormInterface<array<string, mixed>|null> $form
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
     * Submitting is the only decision a company gets, so the other wording is only ever reached if the workflow grows
     * one that is theirs to make.
     */
    #[Override]
    protected function decisionFlash(string $transition): string
    {
        return 'submit' === $transition
            ? $this->translator->trans('Your profile was submitted for review.')
            : $this->translator->trans('Your profile was updated.');
    }

    #[Override]
    protected function reviewAudience(): RevisionAudience
    {
        return RevisionAudience::Everyone;
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
