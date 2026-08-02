<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\Career\VacancyRevisionComment;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Form\Application\ReviewDecisionType;
use App\Form\Career\VacancyType;
use App\Repository\Career\VacancyRepository;
use App\Repository\Career\VacancyRevisionCommentRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\EditLockService;
use App\Service\Career\CareerDraftDiscarder;
use App\Workflow\RevisionClonerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function trim;

/**
 * A company's own vacancies: what it has posted, what it is working on, and where each proposal stands.
 *
 * Everything is scoped to the signed-in representative's company. A vacancy reached by id is checked against it, so a
 * crafted URL lands on a 404 rather than on somebody else's posting.
 */
#[IsGranted(
    attribute: UserRoles::Company->value,
    message: 'You are not allowed to view companies.',
)]
#[Route(
    path: '/company/vacancies',
    name: 'company/',
)]
class CompanyVacancyController extends AbstractController
{
    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly VacancyRevisionCommentRepository $commentRepository,
        private readonly EditLockService $editLockService,
        private readonly RevisionClonerRegistry $clonerRegistry,
        private readonly CareerDraftDiscarder $draftDiscarder,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        #[Target('revisionStateMachine')]
        private readonly WorkflowInterface $revisionStateMachine,
    ) {
    }

    #[Route(
        path: '',
        name: 'vacancies',
        methods: ['GET'],
    )]
    public function index(
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        return $this->render(
            'career/company/vacancies.html.twig',
            [
                'company' => $company,
                'vacancies' => $this->vacancyRepository->findAllForCompany($company),
            ],
        );
    }

    #[Route(
        path: '/create',
        name: 'vacancies/create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(
        Request $request,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        $vacancy = new Vacancy();
        $vacancy->setPublished(true);

        $revision = $this->newDraftRevision();
        $revision->setAuthorCompanyUser($companyUser);
        $vacancy->addRevision($revision);
        $vacancy->setCurrentRevision($revision);

        $form = $this->createForm(
            VacancyType::class,
            $vacancy,
            ['company' => $company],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/company/vacancy-edit.html.twig',
                [
                    'form' => $form,
                    'company' => $company,
                    'vacancy' => null,
                ],
            );
        }

        $this->entityManager->persist($vacancy);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your vacancy is saved as a draft. Submit it for review when you are ready.'),
        );

        return $this->redirectToRoute(
            'company/vacancies/status',
            ['vacancy' => $vacancy->getId()],
        );
    }

    #[Route(
        path: '/{vacancy}/edit',
        name: 'vacancies/edit',
        requirements: ['vacancy' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        Vacancy $vacancy,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $this->requireOwn(
            $vacancy,
            $companyUser,
        );
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $vacancy,
        );

        $current = $this->requireCurrentRevision($vacancy);

        if (!$current->getStatus()->isEditableByAuthor()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This vacancy is not a draft right now. Revise it to start a new one.'),
            );

            return $this->backToStatus($vacancy);
        }

        if (
            null === $this->editLockService->acquire(
                $vacancy,
                $companyUser,
            )
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Somebody else is editing this vacancy right now.'),
            );

            return $this->backToStatus($vacancy);
        }

        $form = $this->createForm(
            VacancyType::class,
            $vacancy,
            ['company' => $company],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/company/vacancy-edit.html.twig',
                [
                    'form' => $form,
                    'company' => $company,
                    'vacancy' => $vacancy,
                    'comments' => $this->commentRepository->findThreadForVacancy($vacancy),
                ],
            );
        }

        $current->setLastEditedByCompanyUser($companyUser);
        $this->entityManager->flush();
        $this->editLockService->release(
            $vacancy,
            $companyUser,
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your changes are saved. Submit them for review when you are ready.'),
        );

        return $this->backToStatus($vacancy);
    }

    #[Route(
        path: '/{vacancy}/revise',
        name: 'vacancies/revise',
        requirements: ['vacancy' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_vacancy_revise-" ~ args["vacancy"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function revise(
        Vacancy $vacancy,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $vacancy,
        );

        $current = $this->requireCurrentRevision($vacancy);

        if ($current->getStatus()->isEditableByAuthor()) {
            return $this->redirectToRoute(
                'company/vacancies/edit',
                ['vacancy' => $vacancy->getId()],
            );
        }

        if (RevisionStatus::Closed === $current->getStatus()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This vacancy was closed. Get in touch with the committee to reopen it.'),
            );

            return $this->backToStatus($vacancy);
        }

        $draft = $this->clonerRegistry->cloneAsDraft($current);
        if (!$draft instanceof VacancyRevision) {
            throw $this->createNotFoundException();
        }

        $draft->setAuthorCompanyUser($companyUser);
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $this->redirectToRoute(
            'company/vacancies/edit',
            ['vacancy' => $vacancy->getId()],
        );
    }

    #[Route(
        path: '/{vacancy}/status',
        name: 'vacancies/status',
        requirements: ['vacancy' => '\d+'],
        methods: ['GET'],
    )]
    public function status(
        Vacancy $vacancy,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );

        return $this->renderStatus(
            $vacancy,
            $this->createDecisionForm($vacancy),
        );
    }

    #[Route(
        path: '/{vacancy}/decide',
        name: 'vacancies/decide',
        requirements: ['vacancy' => '\d+'],
        methods: ['POST'],
    )]
    public function decide(
        Request $request,
        Vacancy $vacancy,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );
        $current = $this->requireCurrentRevision($vacancy);

        $form = $this->createDecisionForm($vacancy)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderStatus(
                $vacancy,
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

            return $this->backToStatus($vacancy);
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
            $this->translator->trans('Your vacancy was submitted for review.'),
        );

        return $this->backToStatus($vacancy);
    }

    #[Route(
        path: '/{vacancy}/comment',
        name: 'vacancies/comment',
        requirements: ['vacancy' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_vacancy_comment-" ~ args["vacancy"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function comment(
        Request $request,
        Vacancy $vacancy,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );
        $current = $this->requireCurrentRevision($vacancy);

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

        return $this->backToStatus($vacancy);
    }

    /**
     * Throw away a draft and go back to what is live, for when a proposal turns out to be a dead end.
     */
    #[Route(
        path: '/{vacancy}/discard',
        name: 'vacancies/discard',
        requirements: ['vacancy' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_vacancy_discard-" ~ args["vacancy"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discard(
        Vacancy $vacancy,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );
        $current = $this->requireCurrentRevision($vacancy);

        $this->denyAccessUnlessGranted(
            RevisionVoter::EDIT,
            $current,
        );

        $live = $vacancy->getLiveRevision();
        if (
            RevisionStatus::Draft !== $current->getStatus()
            || null === $live
            || $live === $current
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This draft cannot be discarded.'),
            );

            return $this->backToStatus($vacancy);
        }

        $this->draftDiscarder->discardVacancyDraft($current);
        $this->editLockService->purge($vacancy);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The draft was discarded and your live vacancy restored.'),
        );

        return $this->redirectToRoute('company/vacancies');
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function renderStatus(
        Vacancy $vacancy,
        FormInterface $form,
    ): Response {
        $current = $this->requireCurrentRevision($vacancy);
        $live = $vacancy->getLiveRevision();

        return $this->render(
            'career/company/vacancy-status.html.twig',
            [
                'vacancy' => $vacancy,
                'revision' => $current,
                'previous' => $current->getPreviousRevision(),
                'comments' => $this->commentRepository->findThreadForVacancy($vacancy),
                'decisionForm' => $form->createView(),
                'canDiscard' => RevisionStatus::Draft === $current->getStatus()
                    && null !== $live
                    && $live !== $current,
            ],
        );
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    private function createDecisionForm(Vacancy $vacancy): FormInterface
    {
        $current = $this->requireCurrentRevision($vacancy);

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

    /**
     * A vacancy reached by id must belong to the signed-in representative's company; anything else is a 404, not a
     * refusal, so a crafted URL says nothing about what exists.
     */
    private function requireOwn(
        Vacancy $vacancy,
        CompanyUser $companyUser,
    ): Company {
        $company = $companyUser->getCompany();
        if ($vacancy->getCompany() !== $company) {
            throw new NotFoundHttpException();
        }

        return $company;
    }

    private function requireCurrentRevision(Vacancy $vacancy): VacancyRevision
    {
        $current = $vacancy->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        return $current;
    }

    private function backToStatus(Vacancy $vacancy): Response
    {
        return $this->redirectToRoute(
            'company/vacancies/status',
            ['vacancy' => $vacancy->getId()],
        );
    }

    private function addComment(
        VacancyRevision $revision,
        CompanyUser $companyUser,
        string $message,
    ): void {
        $comment = new VacancyRevisionComment();
        $comment->setRevision($revision);
        $comment->setAuthorCompanyUser($companyUser);
        $comment->setBody($message);

        $this->entityManager->persist($comment);
    }

    private function newDraftRevision(): VacancyRevision
    {
        $revision = new VacancyRevision();
        $revision->setName(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setLocation(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setWebsite(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setDescription(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setAttachment(new CareerLocalisedText(
            null,
            null,
        ));
        $revision->setCategory(VacancyCategories::Jobs);

        return $revision;
    }
}
