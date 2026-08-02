<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\CompanyRevisionComment;
use App\Entity\Career\VacancyRevision;
use App\Entity\Career\VacancyRevisionComment;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Application\ReviewDecisionType;
use App\Repository\Career\CompanyPackageRepository;
use App\Repository\Career\CompanyRevisionCommentRepository;
use App\Repository\Career\CompanyRevisionRepository;
use App\Repository\Career\VacancyRevisionCommentRepository;
use App\Repository\Career\VacancyRevisionRepository;
use App\Security\Application\RevisionVoter;
use App\Security\User\SudoVoter;
use App\Service\Application\EditLockService;
use App\Service\Career\CareerDraftDiscarder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
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
 * The review surface for the career module: one queue holding both the company profiles and the vacancies waiting for
 * the committee, and a per-revision screen showing what changed against the revision before it, the discussion, and
 * whichever transitions the workflow allows the person looking at it.
 *
 * The same screen serves the committee and the company that proposed the change. Which buttons appear is left to the
 * workflow guards rather than decided here, so a company only ever sees "submit for review" and never has to be told
 * apart from a reviewer in this code.
 */
#[IsGranted(
    attribute: UserRoles::CompanyAdmin->value,
    message: 'You are not allowed to administer companies.',
)]
#[Route(
    path: '/admin/career/approvals',
    name: 'admin/career/approvals/',
)]
class AdminApprovalController extends AbstractController
{
    public function __construct(
        private readonly CompanyRevisionRepository $companyRevisionRepository,
        private readonly VacancyRevisionRepository $vacancyRevisionRepository,
        private readonly CompanyRevisionCommentRepository $companyCommentRepository,
        private readonly VacancyRevisionCommentRepository $vacancyCommentRepository,
        private readonly CompanyPackageRepository $packageRepository,
        private readonly CareerDraftDiscarder $draftDiscarder,
        private readonly EditLockService $editLockService,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        #[Target('revisionStateMachine')]
        private readonly WorkflowInterface $revisionStateMachine,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render(
            'career/admin/approvals/index.html.twig',
            [
                'companyRevisions' => $this->companyRevisionRepository->findForReview(),
                'vacancyRevisions' => $this->vacancyRevisionRepository->findForReview(),
                'pendingBanners' => $this->packageRepository->findPendingBanners(),
            ],
        );
    }

    #[Route(
        path: '/company/{revision}',
        name: 'company',
        requirements: ['revision' => '\d+'],
    )]
    public function reviewCompany(CompanyRevision $revision): Response
    {
        return $this->renderReviewFor(
            $revision,
            $this->createDecisionForm($revision),
        );
    }

    #[Route(
        path: '/vacancy/{revision}',
        name: 'vacancy',
        requirements: ['revision' => '\d+'],
    )]
    public function reviewVacancy(VacancyRevision $revision): Response
    {
        return $this->renderReviewFor(
            $revision,
            $this->createDecisionForm($revision),
        );
    }

    #[Route(
        path: '/company/{revision}/decide',
        name: 'company/decide',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    public function decideCompany(
        Request $request,
        CompanyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->decide(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/vacancy/{revision}/decide',
        name: 'vacancy/decide',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    public function decideVacancy(
        Request $request,
        VacancyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->decide(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/company/{revision}/comment',
        name: 'company/comment',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function commentCompany(
        Request $request,
        CompanyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->comment(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/vacancy/{revision}/comment',
        name: 'vacancy/comment',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function commentVacancy(
        Request $request,
        VacancyRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->comment(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/company/{revision}/discard',
        name: 'company/discard',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_discard-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discardCompany(CompanyRevision $revision): Response
    {
        $this->denyAccessUnlessGranted(
            RevisionVoter::EDIT,
            $revision,
        );

        $company = $revision->getCompany();
        if (!$this->isDiscardable($revision)) {
            return $this->refuseDiscard($revision);
        }

        $this->draftDiscarder->discardCompanyDraft($revision);
        $this->editLockService->purge($company);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The draft was discarded and the live version restored.'),
        );

        return $this->redirectToRoute(
            'admin/career/companies/view',
            ['company' => $company->getId()],
        );
    }

    #[Route(
        path: '/vacancy/{revision}/discard',
        name: 'vacancy/discard',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"career_discard-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discardVacancy(VacancyRevision $revision): Response
    {
        $this->denyAccessUnlessGranted(
            RevisionVoter::EDIT,
            $revision,
        );

        $vacancy = $revision->getVacancy();
        if (!$this->isDiscardable($revision)) {
            return $this->refuseDiscard($revision);
        }

        $this->draftDiscarder->discardVacancyDraft($revision);
        $this->editLockService->purge($vacancy);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The draft was discarded and the live version restored.'),
        );

        return $this->redirectToRoute('admin/career/vacancies/index');
    }

    private function decide(
        Request $request,
        CompanyRevision|VacancyRevision $revision,
        User $user,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::VIEW,
            $revision,
        );

        $form = $this->createDecisionForm($revision)->handleRequest($request);

        // The clicked button names the transition; the form's validation groups make feedback mandatory for a
        // rejection or a request for changes. On any error the review screen comes back with it.
        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderReviewFor(
                $revision,
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
                $revision,
                $transition,
            )
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('That action is not available for this revision.'),
            );

            return $this->redirectToRoute(
                $this->reviewRoute($revision),
                ['revision' => $revision->getId()],
            );
        }

        // Everything but the author's own submit is a reviewer action, so it needs a fresh sudo grant. Opening the
        // screen already asked, so this normally passes; it fires when that grant has lapsed in the meantime.
        if ('submit' !== $transition) {
            $this->denyAccessUnlessGranted(SudoVoter::ATTRIBUTE);
        }

        $message = $form->has('message')
            ? trim(strval($form->get('message')->getData()))
            : '';
        if ('' !== $message) {
            $this->addComment(
                $revision,
                $user,
                $message,
            );
        }

        $this->revisionStateMachine->apply(
            $revision,
            $transition,
        );
        $this->entityManager->flush();

        // trans() is called per arm (not around the match) so each literal stays statically extractable.
        $this->addFlash(
            AlertTypes::Success->value,
            match ($transition) {
                'submit' => $this->translator->trans('Submitted for review.'),
                'start_review' => $this->translator->trans('Review started.'),
                default => $this->translator->trans('The revision was updated.'),
            },
        );

        // Starting a review stays on the screen so the committee can decide straight away; every other decision
        // returns to the queue.
        if ('start_review' === $transition) {
            return $this->redirectToRoute(
                $this->reviewRoute($revision),
                ['revision' => $revision->getId()],
            );
        }

        return $this->redirectToRoute('admin/career/approvals/index');
    }

    private function comment(
        Request $request,
        CompanyRevision|VacancyRevision $revision,
        User $user,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::COMMENT,
            $revision,
        );

        $message = trim(strval($request->request->get('message', '')));
        if ('' !== $message) {
            $this->addComment(
                $revision,
                $user,
                $message,
            );
            $this->entityManager->flush();
        }

        return $this->redirectToRoute(
            $this->reviewRoute($revision),
            ['revision' => $revision->getId()],
        );
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function renderReviewFor(
        CompanyRevision|VacancyRevision $revision,
        FormInterface $form,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::VIEW,
            $revision,
        );

        // Reviewing is sensitive, so a reviewer must be in sudo mode to open the screen. This is a GET, so the sudo
        // listener brings them back here afterwards. Somebody who can only submit their own draft is never asked.
        if (
            $this->isGranted(
                RevisionVoter::APPROVE,
                $revision,
            )
        ) {
            $this->denyAccessUnlessGranted(SudoVoter::ATTRIBUTE);
        }

        if ($revision instanceof CompanyRevision) {
            $company = $revision->getCompany();
            $subjectName = $company->getName();
            $comments = $this->companyCommentRepository->findThreadForCompany($company);
            $prefix = 'admin/career/approvals/company';
        } else {
            $vacancy = $revision->getVacancy();
            $subjectName = $vacancy->getSlugName();
            $comments = $this->vacancyCommentRepository->findThreadForVacancy($vacancy);
            $prefix = 'admin/career/approvals/vacancy';
        }

        return $this->render(
            'career/admin/approvals/review.html.twig',
            [
                'revision' => $revision,
                'previous' => $revision->getPreviousRevision(),
                'isCompany' => $revision instanceof CompanyRevision,
                'subjectName' => $subjectName,
                'comments' => $comments,
                'decisionForm' => $form->createView(),
                'canDiscard' => $this->isDiscardable($revision),
                'decideRoute' => $prefix . '/decide',
                'commentRoute' => $prefix . '/comment',
                'discardRoute' => $prefix . '/discard',
            ],
        );
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    private function createDecisionForm(AbstractRevision $revision): FormInterface
    {
        // Ask the workflow which transitions are enabled for this revision and this user (it already applies the
        // guards) rather than keeping a list here, so a newly added transition shows up on its own.
        $enabled = [];
        foreach ($this->revisionStateMachine->getEnabledTransitions($revision) as $transition) {
            $enabled[] = $transition->getName();
        }

        return $this->createForm(
            ReviewDecisionType::class,
            null,
            [
                'enabled_transitions' => $enabled,
                'resubmission' => RevisionStatus::Draft === $revision->getStatus()
                    && RevisionStatus::ChangesRequested === $revision->getPreviousRevision()?->getStatus(),
            ],
        );
    }

    /**
     * A draft of something that is already live can be thrown away to get back to that live version. The very first
     * draft has nothing behind it, so discarding it would be a deletion instead.
     */
    private function isDiscardable(CompanyRevision|VacancyRevision $revision): bool
    {
        $live = $revision instanceof CompanyRevision
            ? $revision->getCompany()->getLiveRevision()
            : $revision->getVacancy()->getLiveRevision();

        return RevisionStatus::Draft === $revision->getStatus()
            && null !== $live
            && $live !== $revision;
    }

    private function refuseDiscard(CompanyRevision|VacancyRevision $revision): Response
    {
        $this->addFlash(
            AlertTypes::Warning->value,
            $this->translator->trans('This draft cannot be discarded.'),
        );

        return $this->redirectToRoute(
            $this->reviewRoute($revision),
            ['revision' => $revision->getId()],
        );
    }

    private function reviewRoute(CompanyRevision|VacancyRevision $revision): string
    {
        return $revision instanceof CompanyRevision
            ? 'admin/career/approvals/company'
            : 'admin/career/approvals/vacancy';
    }

    private function addComment(
        CompanyRevision|VacancyRevision $revision,
        User $user,
        string $message,
    ): void {
        if ($revision instanceof CompanyRevision) {
            $comment = new CompanyRevisionComment();
            $comment->setRevision($revision);
        } else {
            $comment = new VacancyRevisionComment();
            $comment->setRevision($revision);
        }

        $comment->setAuthor($user);
        $comment->setBody($message);

        $this->entityManager->persist($comment);
    }
}
