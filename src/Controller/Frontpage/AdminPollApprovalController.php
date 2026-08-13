<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Controller\Application\AbstractRevisionReviewController;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\RevisionInterface;
use App\Entity\Frontpage\PollRevision;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Frontpage\PollReviewDecisionType;
use App\Repository\Frontpage\PollRevisionCommentRepository;
use App\Repository\Frontpage\PollRevisionRepository;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\RevisionActions;
use DateTime;
use Override;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function assert;

/**
 * The board's side of a poll: the questions waiting on a decision, and a screen per question showing what it asks,
 * what it can be answered with and how that compares to a wording the board already turned down.
 *
 * Agreeing to a question is also scheduling it, so the closing date is filled in here rather than by whoever asked.
 * There is no discard route: a poll has no draft to throw away.
 */
#[Route(
    path: '/admin/polls/approvals',
    name: 'admin/frontpage/polls/approvals/',
)]
class AdminPollApprovalController extends AbstractRevisionReviewController
{
    public function __construct(
        private readonly PollRevisionRepository $revisionRepository,
        private readonly PollRevisionCommentRepository $commentRepository,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    #[IsGranted(UserRoles::Board->value)]
    public function index(): Response
    {
        return $this->render(
            'frontpage/admin/polls/approvals/index.html.twig',
            [
                'rows' => ReviewQueueRow::fromRevisions(
                    $this->revisionRepository->findForReview(),
                    static function (RevisionInterface $revision): string {
                        assert($revision instanceof PollRevision);

                        return $revision->getQuestion()->getText(Languages::current()) ?? '';
                    },
                    'admin/frontpage/polls/approvals/review',
                ),
            ],
        );
    }

    #[Route(
        path: '/{revision}',
        name: 'review',
        requirements: ['revision' => '\d+'],
    )]
    public function review(PollRevision $revision): Response
    {
        $this->assertMayReview($revision);

        return $this->renderReview($revision);
    }

    #[Route(
        path: '/{revision}/decide',
        name: 'decide',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    public function decide(
        Request $request,
        PollRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->handleDecision(
            $request,
            $revision,
            $user,
        );
    }

    #[Route(
        path: '/{revision}/comment',
        name: 'comment',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"poll_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function comment(
        Request $request,
        PollRevision $revision,
        #[CurrentUser]
        User $user,
    ): Response {
        $this->handleCommentPost(
            $request,
            $revision,
            $user,
        );

        return $this->reviewResponse($revision);
    }

    /**
     * The decision form carries the closing date next to the approve button.
     */
    #[Override]
    protected function createDecisionForm(RevisionActions $actions): FormInterface
    {
        return $this->createForm(
            PollReviewDecisionType::class,
            null,
            $actions->toFormOptions(),
        );
    }

    /**
     * Approving is also scheduling: the closing date is set before the decision is applied, so it is written in the
     * same flush as the approval.
     */
    #[Override]
    protected function applyDecision(
        FormInterface $form,
        RevisionInterface $revision,
        User|CompanyUser $actor,
    ): ?string {
        assert($revision instanceof PollRevision);

        if ('approve' === $this->clickedTransition($form)) {
            $expiryDate = $form->get('expiryDate')->getData();
            assert($expiryDate instanceof DateTime);

            $revision->getPoll()->setExpiryDate($expiryDate);
        }

        return parent::applyDecision(
            $form,
            $revision,
            $actor,
        );
    }

    #[Override]
    protected function reviewTemplate(): string
    {
        return 'frontpage/admin/polls/approvals/review.html.twig';
    }

    /**
     * trans() is called per arm (not around the match) so each literal stays statically extractable.
     */
    #[Override]
    protected function decisionFlash(string $transition): string
    {
        return match ($transition) {
            'start_review' => $this->translator->trans('Review started.'),
            'approve' => $this->translator->trans('The poll is live and will close on the date you gave.'),
            default => $this->translator->trans('The poll was turned down.'),
        };
    }

    /**
     * Starting a review stays on the screen so the board can decide straight away; a decision returns to the queue.
     */
    #[Override]
    protected function decisionResponse(
        RevisionInterface $revision,
        string $transition,
    ): Response {
        return match ($transition) {
            'start_review' => $this->reviewResponse($revision),
            default => $this->redirectToRoute('admin/frontpage/polls/approvals/index'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function reviewContext(
        RevisionInterface $revision,
        RevisionActions $actions,
    ): array {
        assert($revision instanceof PollRevision);

        return [
            'poll' => $revision->getPoll(),
            'subjectName' => $revision->getQuestion()->getText(Languages::current()) ?? '',
            'comments' => $this->commentRepository->findThreadForPoll($revision->getPoll()),
        ];
    }

    #[Override]
    protected function reviewResponse(RevisionInterface $revision): Response
    {
        return $this->redirectToRoute(
            'admin/frontpage/polls/approvals/review',
            ['revision' => $revision->getId()],
        );
    }
}
