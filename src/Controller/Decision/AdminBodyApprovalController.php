<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Controller\Application\AbstractRevisionReviewController;
use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\OrganInformationRevisionCommentRepository;
use App\Service\Decision\OrganInformationReviewQueueProvider;
use App\ViewModel\Application\RevisionActions;
use Override;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function assert;

/**
 * The review surface for what bodies write about themselves: a queue of the pages waiting on the board, and a screen
 * per revision showing what changed against the one before it, the discussion, and whichever transitions the workflow
 * allows whoever is looking.
 *
 * The same screen serves the board and the body that submitted, which is why only the queue is board-only. Which
 * buttons appear is left to the workflow guards rather than decided here, so the screen never has to tell a reviewer
 * apart from an author.
 */
#[Route(
    path: '/admin/decision/bodies/approvals',
    name: 'admin/decision/bodies/approvals/',
)]
class AdminBodyApprovalController extends AbstractRevisionReviewController
{
    public function __construct(
        private readonly OrganInformationReviewQueueProvider $queueProvider,
        private readonly OrganInformationRevisionCommentRepository $commentRepository,
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
            'decision/admin/bodies/approvals/index.html.twig',
            ['rows' => $this->queueProvider->queue()->rows],
        );
    }

    #[Route(
        path: '/{revision}',
        name: 'review',
        requirements: ['revision' => '\d+'],
    )]
    public function review(OrganInformationRevision $revision): Response
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
        OrganInformationRevision $revision,
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
        id: new Expression('"body_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function comment(
        Request $request,
        OrganInformationRevision $revision,
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
     * Throw a draft away and point the page back at what is on the website, which is how a body abandons a change it
     * thought better of.
     */
    #[Route(
        path: '/{revision}/discard',
        name: 'discard',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"body_discard-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discard(OrganInformationRevision $revision): Response
    {
        return $this->discardDraft(
            $revision,
            'admin/decision/bodies/view',
            ['organ' => $revision->getOrgan()->getId()],
        );
    }

    #[Override]
    protected function reviewTemplate(): string
    {
        return 'decision/admin/bodies/approvals/review.html.twig';
    }

    /**
     * trans() is called per arm (not around the match) so each literal stays statically extractable.
     */
    #[Override]
    protected function decisionFlash(string $transition): string
    {
        return match ($transition) {
            'submit' => $this->translator->trans('Submitted for review.'),
            'start_review' => $this->translator->trans('Review started.'),
            default => $this->translator->trans('The page was updated.'),
        };
    }

    /**
     * A body that submits goes back to its own page; starting a review stays on the screen so the board can decide
     * straight away; every other decision returns to the queue.
     */
    #[Override]
    protected function decisionResponse(
        RevisionInterface $revision,
        string $transition,
    ): Response {
        assert($revision instanceof OrganInformationRevision);

        return match ($transition) {
            'submit' => $this->redirectToRoute(
                'admin/decision/bodies/view',
                ['organ' => $revision->getOrgan()->getId()],
            ),
            'start_review' => $this->reviewResponse($revision),
            default => $this->redirectToRoute('admin/decision/bodies/approvals/index'),
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
        assert($revision instanceof OrganInformationRevision);

        return [
            'organ' => $revision->getOrgan(),
            'subjectName' => $revision->getOrgan()->getAbbr(),
            'comments' => $this->commentRepository->findThreadForOrganInformation($revision->getOrganInformation()),
        ];
    }

    #[Override]
    protected function reviewResponse(RevisionInterface $revision): Response
    {
        return $this->redirectToRoute(
            'admin/decision/bodies/approvals/review',
            ['revision' => $revision->getId()],
        );
    }
}
