<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\CompanyRevisionComment;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Form\Application\ReviewDecisionType;
use App\Form\Career\CompanyType;
use App\Repository\Career\CompanyAuditLogRepository;
use App\Repository\Career\CompanyRevisionCommentRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\EditLockService;
use App\Service\Career\CompanyAuditLogger;
use App\Service\Career\CompanyImageUploadService;
use App\Workflow\RevisionClonerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function trim;

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
class CompanyProfileController extends AbstractController
{
    public function __construct(
        private readonly CompanyRevisionCommentRepository $commentRepository,
        private readonly CompanyAuditLogRepository $auditLogRepository,
        private readonly CompanyAuditLogger $auditLogger,
        private readonly CompanyImageUploadService $imageUploadService,
        private readonly EditLockService $editLockService,
        private readonly RevisionClonerRegistry $clonerRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        #[Target('revisionStateMachine')]
        private readonly WorkflowInterface $revisionStateMachine,
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
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Somebody else is editing the profile right now.'),
            );

            return $this->redirectToRoute('company/profile');
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
            $this->createDecisionForm($company),
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

        $form = $this->createDecisionForm($company)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderStatus(
                $company,
                $form,
            );
        }

        $transition = '';
        if ($form instanceof Form) {
            $button = $form->getClickedButton();
            $transition = $button instanceof FormInterface
                ? $button->getName()
                : '';
        }

        // The workflow guards already keep a company to its own transitions, so this only reports the ones that have
        // become unavailable since the page was drawn.
        if (
            !$this->revisionStateMachine->can(
                $current,
                $transition,
            )
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('That action is not available right now.'),
            );

            return $this->redirectToRoute('company/profile/status');
        }

        $message = $form->has('message')
            ? trim(strval($form->get('message')->getData()))
            : '';
        if ('' !== $message) {
            $this->addComment(
                $current,
                $companyUser,
                $message,
            );
        }

        $this->revisionStateMachine->apply(
            $current,
            $transition,
        );
        $this->entityManager->flush();

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

        $this->denyAccessUnlessGranted(
            RevisionVoter::COMMENT,
            $current,
        );

        $message = trim(strval($request->request->get('message', '')));
        if ('' !== $message) {
            $this->addComment(
                $current,
                $companyUser,
                $message,
            );
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('company/profile/status');
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function renderStatus(
        Company $company,
        FormInterface $form,
    ): Response {
        $current = $this->requireCurrentRevision($company);

        return $this->render(
            'career/company/profile-status.html.twig',
            [
                'company' => $company,
                'revision' => $current,
                'previous' => $current->getPreviousRevision(),
                'comments' => $this->commentRepository->findThreadForCompany($company),
                'decisionForm' => $form->createView(),
            ],
        );
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    private function createDecisionForm(Company $company): FormInterface
    {
        $current = $this->requireCurrentRevision($company);

        $enabled = [];
        foreach ($this->revisionStateMachine->getEnabledTransitions($current) as $transition) {
            $enabled[] = $transition->getName();
        }

        return $this->createForm(
            ReviewDecisionType::class,
            null,
            [
                'enabled_transitions' => $enabled,
                'resubmission' => RevisionStatus::Draft === $current->getStatus()
                    && RevisionStatus::ChangesRequested === $current->getPreviousRevision()?->getStatus(),
            ],
        );
    }

    private function requireCurrentRevision(Company $company): CompanyRevision
    {
        $current = $company->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        return $current;
    }

    private function addComment(
        CompanyRevision $revision,
        CompanyUser $companyUser,
        string $message,
    ): void {
        $comment = new CompanyRevisionComment();
        $comment->setRevision($revision);
        $comment->setAuthorCompanyUser($companyUser);
        $comment->setBody($message);

        $this->entityManager->persist($comment);
    }
}
