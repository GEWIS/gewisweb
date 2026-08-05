<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Controller\Application\AbstractRevisionReviewController;
use App\Controller\Application\HoldsEditLockTrait;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\ReviseRefusal;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Form\Career\VacancyType;
use App\Repository\Career\VacancyRepository;
use App\Repository\Career\VacancyRevisionCommentRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\RevisionDiscarder;
use App\Service\Application\RevisionReviser;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\RevisionActions;
use App\ViewModel\Career\Portal\CompanyVacancyOverview;
use Override;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function assert;

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
class CompanyVacancyController extends AbstractRevisionReviewController
{
    use HoldsEditLockTrait;

    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly VacancyRevisionCommentRepository $commentRepository,
        private readonly RevisionReviser $reviser,
        private readonly RevisionDiscarder $draftDiscarder,
    ) {
    }

    #[Route(
        path: '',
        name: 'vacancies',
        methods: ['GET'],
    )]
    public function index(
        Request $request,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();

        return $this->render(
            'career/company/vacancies.html.twig',
            [
                'company' => $company,
                'overview' => CompanyVacancyOverview::build(
                    $this->vacancyRepository->findAllForCompany($company),
                    $request->query->getString('filter'),
                ),
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

        $revision = new VacancyRevision();
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
            return $this->render(
                'career/company/edit-locked.html.twig',
                [
                    'lock' => $this->editLockService->blockingLock(
                        $vacancy,
                        $companyUser,
                    ),
                    'backRoute' => 'company/vacancies',
                    'subject' => $this->translator->trans('this vacancy'),
                ],
            );
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
        path: '/{vacancy}/edit/ping',
        name: 'vacancies/edit_ping',
        requirements: ['vacancy' => '\\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_vacancy_edit_lock-" ~ args["vacancy"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function editPing(
        #[CurrentUser]
        CompanyUser $companyUser,
        Vacancy $vacancy,
    ): JsonResponse {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );

        return $this->pingLock(
            $vacancy,
            $companyUser,
        );
    }

    #[Route(
        path: '/{vacancy}/edit/release',
        name: 'vacancies/edit_release',
        requirements: ['vacancy' => '\\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_vacancy_edit_lock-" ~ args["vacancy"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function editRelease(
        #[CurrentUser]
        CompanyUser $companyUser,
        Vacancy $vacancy,
    ): JsonResponse {
        $this->requireOwn(
            $vacancy,
            $companyUser,
        );

        return $this->releaseLock(
            $vacancy,
            $companyUser,
        );
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

        $refusal = $current->getStatus()->reviseRefusal();

        // A draft that is already there is what the representative wants to work on, which is not worth a warning.
        if (ReviseRefusal::AlreadyADraft === $refusal) {
            return $this->redirectToRoute(
                'company/vacancies/edit',
                ['vacancy' => $vacancy->getId()],
            );
        }

        if (ReviseRefusal::UnderReview === $refusal) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans(
                    'This vacancy is with the committee. Wait for their decision before revising it again.',
                ),
            );

            return $this->backToStatus($vacancy);
        }

        if (ReviseRefusal::Closed === $refusal) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This vacancy was closed. Get in touch with the committee to reopen it.'),
            );

            return $this->backToStatus($vacancy);
        }

        $draft = $this->reviser->spawnDraft(
            $current,
            $companyUser,
        );
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
            $this->createDecisionForm($this->revisionActions($this->requireCurrentRevision($vacancy))),
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

        return $this->handleDecision(
            $request,
            $this->requireCurrentRevision($vacancy),
            $companyUser,
        );
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

        $this->handleCommentPost(
            $request,
            $current,
            $companyUser,
        );

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

        if (!$this->revisionActions($current)->isDiscardable) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This draft cannot be discarded.'),
            );

            return $this->backToStatus($vacancy);
        }

        $this->draftDiscarder->discardToLive($current);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The draft was discarded and your live vacancy restored.'),
        );

        return $this->redirectToRoute('company/vacancies');
    }

    /**
     * @param FormInterface<array<string, mixed>|null> $form
     */
    private function renderStatus(
        Vacancy $vacancy,
        ?FormInterface $form = null,
    ): Response {
        return $this->renderReview(
            $this->requireCurrentRevision($vacancy),
            $form,
        );
    }

    #[Override]
    protected function reviewTemplate(): string
    {
        return 'career/company/vacancy-status.html.twig';
    }

    /**
     * Submitting is the only decision a company gets, so the other wording is only ever reached if the workflow grows
     * one that is theirs to make.
     */
    #[Override]
    protected function decisionFlash(string $transition): string
    {
        return 'submit' === $transition
            ? $this->translator->trans('Your vacancy was submitted for review.')
            : $this->translator->trans('Your vacancy was updated.');
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
        assert($revision instanceof VacancyRevision);
        $vacancy = $revision->getVacancy();

        return [
            'vacancy' => $vacancy,
            'comments' => $this->commentRepository->findThreadForVacancy($vacancy),
        ];
    }

    #[Override]
    protected function reviewResponse(RevisionInterface $revision): Response
    {
        assert($revision instanceof VacancyRevision);

        return $this->backToStatus($revision->getVacancy());
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
}
