<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionComment;
use App\Entity\Activity\SignupList;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Application\ReviewDecisionType;
use App\Repository\Activity\ActivityRevisionCommentRepository;
use App\Repository\Activity\ActivityRevisionRepository;
use App\Security\Application\RevisionVoter;
use App\Security\User\SudoVoter;
use App\Service\Activity\DraftDiscarder;
use App\Service\Activity\SignupListMigrator;
use App\Service\Application\EditLockService;
use App\Util\Activity\PastActivityRule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
 * The shared review surface for activities:
 *  - a per-revision screen with a diff against the previous revision
 *  - the discussion thread
 *  - the workflow transitions
 *
 * It is used both by the board (approve/reject/request changes/...) and by the activity's own organisers (submit for
 * review, commenting). The available transitions are whatever the `revision` workflow guards allow the current user, so
 * each role only ever sees its own actions. The submission queue ({@see self::index()}) remains board-only.
 */
#[Route(
    path: '/admin/activities/approvals',
    name: 'admin/activities/approvals/',
)]
class AdminApprovalController extends AbstractController
{
    public function __construct(
        private readonly ActivityRevisionRepository $revisionRepository,
        private readonly ActivityRevisionCommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly WorkflowInterface $revisionStateMachine,
        private readonly SignupListMigrator $signupListMigrator,
        private readonly DraftDiscarder $draftDiscarder,
        private readonly EditLockService $editLockService,
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
            'activity/admin/approvals/index.html.twig',
            ['revisions' => $this->revisionRepository->findForReview()],
        );
    }

    #[Route(
        path: '/{revision}',
        name: 'review',
        requirements: ['revision' => '\d+'],
    )]
    public function review(ActivityRevision $revision): Response
    {
        $this->denyAccessUnlessGranted(
            RevisionVoter::VIEW,
            $revision,
        );

        // Actually reviewing is sensitive, so a reviewer (board, or C4 for company/vacancy) must be in sudo mode to
        // open the screen. This is a GET, so the SudoAccessDeniedListener preserves ?next and returns the reviewer
        // here after re-auth. Pure authors (APPROVE denied) are never prompted: they only view/submit/comment on
        // their own draft, none of which sudo gates.
        if (
            $this->isGranted(
                RevisionVoter::APPROVE,
                $revision,
            )
        ) {
            $this->denyAccessUnlessGranted(SudoVoter::ATTRIBUTE);
        }

        return $this->renderReview(
            $revision,
            $this->createDecisionForm($revision),
        );
    }

    #[Route(
        path: '/{revision}/decide',
        name: 'decide',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    public function decide(
        Request $request,
        #[CurrentUser]
        User $user,
        ActivityRevision $revision,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::VIEW,
            $revision,
        );

        $form = $this->createDecisionForm($revision)->handleRequest($request);

        // The clicked button names the transition; the form's validation groups make feedback mandatory for
        // "reject"/"request changes". On any error (incl. missing feedback) the review screen is re-rendered.
        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderReview(
                $revision,
                $form,
            );
        }

        // getClickedButton() lives on the concrete Form; the clicked submit button names the transition.
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
                'admin/activities/approvals/review',
                ['revision' => $revision->getId()],
            );
        }

        // Defence in depth: a reviewer transition (anything but the author's `submit`) requires sudo before it is
        // applied. review() already prompted within the last 10 minutes, so this normally sees an active grant; it
        // only fires when the grant lapsed, sending the reviewer to confirm sudo and retry.
        if ('submit' !== $transition) {
            $this->denyAccessUnlessGranted(SudoVoter::ATTRIBUTE);
        }

        // The message field is only present for transitions that carry one (a decision, or a resubmission response).
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
                'submit' => $this->translator->trans('Activity submitted for review.'),
                'start_review' => $this->translator->trans('Review started.'),
                default => $this->translator->trans('The activity revision was updated.'),
            },
        );

        // Authors return to their overview; starting a review stays on the (now in-review) screen so the
        // board can decide straight away; every other decision returns to the queue.
        return match ($transition) {
            'submit' => $this->redirectToRoute('admin/activities/index'),
            'start_review' => $this->redirectToRoute(
                'admin/activities/approvals/review',
                ['revision' => $revision->getId()],
            ),
            default => $this->redirectToRoute('admin/activities/approvals/index'),
        };
    }

    #[Route(
        path: '/{revision}/comment',
        name: 'comment',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_review_comment-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function comment(
        Request $request,
        #[CurrentUser]
        User $user,
        ActivityRevision $revision,
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
            'admin/activities/approvals/review',
            ['revision' => $revision->getId()],
        );
    }

    /**
     * Throw away a draft re-edit and point the activity back at its live (approved) version. This is the recovery for
     * a draft whose `submit` the workflow withholds because it restructured a list the live revision now has sign-ups
     * on (see {@see \App\EventListener\Application\SignupMigrationGuardListener}): the diverged structure cannot be
     * fixed in place, so the organiser discards it and revises the live version afresh.
     */
    #[Route(
        path: '/{revision}/discard',
        name: 'discard',
        requirements: ['revision' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_discard-" ~ args["revision"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function discard(ActivityRevision $revision): Response
    {
        // Discarding a draft is editing it away, so it is gated exactly like editing: a Draft, by an owner or reviewer.
        $this->denyAccessUnlessGranted(
            RevisionVoter::EDIT,
            $revision,
        );

        // There must be a live (approved) version to fall back to. A brand-new activity's draft has nothing to revert
        // to, so removing it would delete the whole activity, which is deliberately left to the stale-draft cleanup.
        $live = $revision->getActivity()->getLiveRevision();
        if (
            null === $live
            || $live === $revision
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This draft cannot be discarded.'),
            );

            return $this->redirectToRoute(
                'admin/activities/approvals/review',
                ['revision' => $revision->getId()],
            );
        }

        $this->draftDiscarder->discardToLive($revision);
        // The draft is gone, so its edit lock (keyed on the activity) is meaningless: drop it now instead of leaving a
        // reviewer's discard to block the owner until the lock's TTL lapses. purge() only schedules the removal, so it
        // commits with the discard below.
        $this->editLockService->purge($revision->getActivity());
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Draft discarded; the activity was reverted to its live version.'),
        );

        return $this->redirectToRoute('admin/activities/index');
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    private function createDecisionForm(ActivityRevision $revision): FormInterface
    {
        // Ask the workflow directly which transitions are enabled for this revision and user (it already applies the
        // guards), rather than filtering a hand-maintained list: a newly added transition then shows up automatically.
        $enabled = [];
        foreach ($this->revisionStateMachine->getEnabledTransitions($revision) as $transition) {
            $enabled[] = $transition->getName();
        }

        return $this->createForm(
            ReviewDecisionType::class,
            null,
            [
                'enabled_transitions' => $enabled,
                'resubmission' => $this->isResubmission($revision),
            ],
        );
    }

    /**
     * Whether this draft was spawned to address a "changes requested" review, so resubmitting it must carry a
     * response explaining how the feedback was addressed.
     */
    private function isResubmission(ActivityRevision $revision): bool
    {
        return RevisionStatus::Draft === $revision->getStatus()
            && RevisionStatus::ChangesRequested === $revision->getPreviousRevision()?->getStatus();
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function renderReview(
        ActivityRevision $revision,
        FormInterface $form,
    ): Response {
        // When this revision restructured/removed a sign-up list that the live revision has sign-ups on, the workflow
        // withholds approve/submit (SignupMigrationGuardListener); explain why on the screen.
        $live = $revision->getActivity()->getLiveRevision();
        $migrationBlocked = null !== $live
            && $live !== $revision
            && !$this->signupListMigrator->isMigratable(
                $live,
                $revision,
            );

        // When the activity can no longer be published, the workflow withholds submit/approve
        // (PastActivityGuardListener); explain why on the screen. Two cases: an established activity whose live
        // schedule has *ended*, and a brand-new activity (no live revision) whose own *start* has already passed.
        // The latter is recoverable by re-dating the draft, so it gets a different banner.
        $liveEnded = PastActivityRule::liveEnded(
            $live,
            $revision,
        );
        $debutMissed = PastActivityRule::debutMissed(
            $live,
            $revision,
        );
        $activityPassed = $liveEnded || $debutMissed;

        // A draft re-edit of an already-live activity can be discarded back to that live version (the recovery for a
        // blocked submit, but offered for any such draft). A brand-new activity's draft has no live version to revert
        // to, so it is not discardable here.
        $canDiscard = RevisionStatus::Draft === $revision->getStatus()
            && null !== $live
            && $live !== $revision;

        return $this->render(
            'activity/admin/approvals/review.html.twig',
            [
                'revision' => $revision,
                'activity' => $revision->getActivity(),
                'previous' => $revision->getPreviousRevision(),
                'comments' => $this->commentRepository->findThreadForActivity($revision->getActivity()),
                'decisionForm' => $form->createView(),
                'migrationBlocked' => $migrationBlocked,
                'activityPassed' => $activityPassed,
                'debutMissed' => $debutMissed,
                'canDiscard' => $canDiscard,
                'signupListDiff' => $this->buildSignupListDiff(
                    $revision,
                    $revision->getPreviousRevision(),
                ),
            ],
        );
    }

    /**
     * Match the revision's sign-up lists to the previous revision's by lineage, so the review screen can pair each
     * list with its counterpart (for a field-by-field diff) and flag the ones that are new or were removed. Each
     * present entry also carries `liveAdmitted`: how many sign-ups are already admitted (drawn) on the live revision's
     * counterpart, so the screen can warn when a lowered capacity would sit below the people already let in.
     *
     * @return array{
     *     present: list<array{list: SignupList, previous: SignupList|null, liveAdmitted: int}>,
     *     removed: list<SignupList>,
     * }
     */
    private function buildSignupListDiff(
        ActivityRevision $revision,
        ?ActivityRevision $previous,
    ): array {
        $previousByLineage = [];
        foreach ($previous?->getSignupLists() ?? [] as $list) {
            $previousByLineage[$list->getLineageId()->toRfc4122()] = $list;
        }

        // How many are already admitted on each live list (by lineage), so a capacity drop below it can be flagged.
        // Only meaningful for a live list that was itself limited: on an unlimited list every sign-up is drawn by
        // default (no draw ever ran), so counting it would raise a bogus "capacity below admitted" warning.
        $liveAdmittedByLineage = [];
        foreach ($revision->getActivity()->getLiveRevision()?->getSignupLists() ?? [] as $liveList) {
            if (!$liveList->getLimitedCapacity()) {
                continue;
            }

            $admitted = 0;
            foreach ($liveList->getSignUps() as $signup) {
                if (!$signup->isDrawn()) {
                    continue;
                }

                ++$admitted;
            }

            $liveAdmittedByLineage[$liveList->getLineageId()->toRfc4122()] = $admitted;
        }

        $present = [];
        $seen = [];
        foreach ($revision->getSignupLists() as $list) {
            $key = $list->getLineageId()->toRfc4122();
            $seen[$key] = true;
            $counterpart = $previousByLineage[$key] ?? null;
            $present[] = [
                'list' => $list,
                'previous' => $counterpart,
                'liveAdmitted' => $liveAdmittedByLineage[$key] ?? 0,
            ];
        }

        $removed = [];
        foreach ($previousByLineage as $key => $list) {
            if (isset($seen[$key])) {
                continue;
            }

            $removed[] = $list;
        }

        return [
            'present' => $present,
            'removed' => $removed,
        ];
    }

    private function addComment(
        ActivityRevision $revision,
        User $user,
        string $message,
    ): void {
        $comment = new ActivityRevisionComment();
        $comment->setRevision($revision);
        $comment->setAuthor($user);
        $comment->setBody($message);

        $this->entityManager->persist($comment);
    }
}
