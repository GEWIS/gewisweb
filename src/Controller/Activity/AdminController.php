<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Activity\ActivityType;
use App\Repository\Activity\ActivityRevisionCommentRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Application\EditLockService;
use App\Workflow\RevisionClonerRegistry;
use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;
use function is_int;

#[Route(
    path: '/admin/activities',
    name: 'admin/activities/',
)]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly ActivityRevisionCommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly RevisionClonerRegistry $clonerRegistry,
        private readonly EditLockService $editLockService,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        // The two tables (pending + approved), scoping and the board "show all" toggle live in the
        // Activity:Admin:ActivityOverview live component embedded by this template.
        return $this->render('activity/admin/index.html.twig');
    }

    #[Route(
        path: '/create',
        name: 'create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $activity = new Activity();
        $activity->setCreator($user->getMember());

        $revision = $this->newDraftRevision();
        $revision->setAuthor($user->getMember());
        $activity->addRevision($revision);
        $activity->setCurrentRevision($revision);

        $form = $this->createForm(ActivityType::class, $activity)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'activity/admin/create.html.twig',
                ['form' => $form],
            );
        }

        $this->entityManager->persist($activity);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Activity saved as a draft. Submit it for review when you are ready.'),
        );

        return $this->redirectToRoute('admin/activities/index');
    }

    #[Route(
        path: '/{activity}/edit',
        name: 'edit',
        requirements: ['activity' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        #[CurrentUser]
        User $user,
        Activity $activity,
    ): Response {
        // Owners, organ members and reviewers may revise the activity.
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $activity,
        );

        $current = $activity->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        $status = $current->getStatus();

        // An approved activity that has already taken place is immutable: no further revisions.
        if (
            RevisionStatus::Approved === $status
            && $this->hasPassed($current)
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This activity has already taken place and can no longer be revised.'),
            );

            return $this->redirectToRoute('admin/activities/index');
        }

        // Submitted / in review: locked while it is with the board, before any edit lock is even considered.
        if (
            !$status->isEditableByAuthor()
            && !$status->isTerminal()
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This activity is being reviewed and cannot be edited right now.'),
            );

            return $this->redirectToRoute('admin/activities/index');
        }

        // Acquire the exclusive edit lock before spawning/binding: this is also what prevents two people both spawning
        //a competing draft of the same live activity. A reviewer may force-take an alive lock (?take=1).
        $forceTake = $request->query->getBoolean('take')
            && $this->isGranted(UserRoles::Board->value);
        $lock = $this->editLockService->acquire(
            $activity,
            $user,
            $forceTake,
        );
        if (null === $lock) {
            return $this->renderLocked(
                $activity,
                $user,
            );
        }

        if ($status->isEditableByAuthor()) {
            // A draft is edited in place.
            $revision = $current;
        } else {
            // An approved/rejected activity is revised by spawning a new draft linked to the current revision; the
            // editing member becomes its author.
            $revision = $this->clonerRegistry->cloneAsDraft($current);
            $revision->setAuthor($user->getMember());
        }

        // The registry is typed to the shared RevisionInterface; for an activity it always yields an ActivityRevision.
        assert($revision instanceof ActivityRevision);

        // For a spawned draft the cloner has already pointed the activity's current revision at it; for an in-place
        // draft it was already current.
        $form = $this->createForm(ActivityType::class, $activity)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            if (
                !$form->isSubmitted()
                && null !== $revision->getId()
            ) {
                // Remember, server-side, the version this edit started from, so the optimistic-lock check on save
                // cannot be bypassed by tampering a client-submitted field.
                $request->getSession()->set(
                    $this->editVersionKey($activity),
                    $revision->getVersion(),
                );
            }

            return $this->render(
                'activity/admin/edit.html.twig',
                [
                    'form' => $form,
                    'activity' => $activity,
                    'comments' => $this->commentRepository->findThreadForActivity($activity),
                ],
            );
        }

        // Refuse the save only if the lock was force-taken by SOMEONE ELSE (a reviewer) while this form was open. We
        // use the read-only blockingLock() rather than ping(): ping() flushes, which would commit the bound form
        // changes before the optimistic-version check below and before lastEditedBy is stamped (breaking both), and a
        // lock we self-released on navigation (a page-unload beacon racing the submit) must not count as "taken over".
        if (
            null !== $this->editLockService->blockingLock(
                $activity,
                $user,
            )
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This activity was taken over by a reviewer, so your changes were not saved.'),
            );

            return $this->redirectToRoute('admin/activities/index');
        }

        // Optimistic-locking backstop for an in-place draft edit (a spawned draft is brand-new, nothing to race). The
        // base version is read from the server-side session (stamped when the form was opened), never from the request,
        // so it cannot be forged to slip a stale edit past the check.
        if (null !== $revision->getId()) {
            $baseVersion = $request->getSession()->get($this->editVersionKey($activity));
            if (!is_int($baseVersion)) {
                $this->addFlash(
                    AlertTypes::Warning->value,
                    $this->translator->trans('Your edit session expired; reopen the activity and try again.'),
                );

                return $this->redirectToRoute(
                    'admin/activities/edit',
                    ['activity' => $activity->getId()],
                );
            }

            try {
                $this->entityManager->lock(
                    $revision,
                    LockMode::OPTIMISTIC,
                    $baseVersion,
                );
            } catch (OptimisticLockException) {
                $this->addFlash(
                    AlertTypes::Warning->value,
                    $this->translator->trans('This revision was changed elsewhere; reload the page and try again.'),
                );

                return $this->redirectToRoute(
                    'admin/activities/edit',
                    ['activity' => $activity->getId()],
                );
            }
        }

        $revision->setLastEditedBy($user);
        $this->entityManager->persist($activity);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();
        $this->editLockService->release(
            $activity,
            $user,
        );
        $request->getSession()->remove($this->editVersionKey($activity));

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Changes saved. Submit the revision for review when you are ready.'),
        );

        return $this->redirectToRoute('admin/activities/index');
    }

    /**
     * The "someone else is editing this" screen: shows who holds the lock and, for reviewers, a take-over action.
     */
    private function renderLocked(
        Activity $activity,
        User $user,
    ): Response {
        return $this->render(
            'activity/admin/edit_locked.html.twig',
            [
                'activity' => $activity,
                'lock' => $this->editLockService->blockingLock(
                    $activity,
                    $user,
                ),
            ],
        );
    }

    #[Route(
        path: '/{activity}/edit/ping',
        name: 'edit_ping',
        requirements: ['activity' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_edit_lock-" ~ args["activity"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function editPing(
        #[CurrentUser]
        User $user,
        Activity $activity,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $activity,
        );

        return new JsonResponse([
            'held' => $this->editLockService->ping(
                $activity,
                $user,
            ),
        ]);
    }

    #[Route(
        path: '/{activity}/edit/release',
        name: 'edit_release',
        requirements: ['activity' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_edit_lock-" ~ args["activity"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function editRelease(
        #[CurrentUser]
        User $user,
        Activity $activity,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $activity,
        );

        $this->editLockService->release(
            $activity,
            $user,
        );

        return new JsonResponse(['released' => true]);
    }

    #[Route(
        path: '/{activity}/reopen',
        name: 'reopen',
        requirements: ['activity' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_reopen-" ~ args["activity"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function reopen(
        #[CurrentUser]
        User $user,
        Activity $activity,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::REOPEN,
            $activity,
        );

        $current = $activity->getCurrentRevision();
        if (null === $current) {
            throw $this->createNotFoundException();
        }

        // The cloner links the new draft and points the activity's current revision at it; the reopening member
        // becomes its author.
        $revision = $this->clonerRegistry->cloneAsDraft($current);
        $revision->setAuthor($user->getMember());
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('A new draft was created. Edit it and submit again.'),
        );

        return $this->redirectToRoute(
            'admin/activities/edit',
            ['activity' => $activity->getId()],
        );
    }

    /**
     * Session key under which the version an in-place edit started from is stamped (per activity), so the
     * optimistic-lock check on save reads a server-trusted base version instead of a client-submitted one.
     */
    private function editVersionKey(Activity $activity): string
    {
        return 'activity-edit-base-version-' . $activity->getId();
    }

    /**
     * A blank draft revision with its localised texts and required scalar fields initialised, so the create form can
     * bind to it.
     */
    private function newDraftRevision(): ActivityRevision
    {
        $revision = new ActivityRevision();
        $revision->setName(new ActivityLocalisedText());
        $revision->setLocation(new ActivityLocalisedText());
        $revision->setCosts(new ActivityLocalisedText());
        $revision->setDescription(new ActivityLocalisedText());
        $revision->setCategory(ActivityCategories::Other);

        // The schedule is intentionally left empty (not pre-filled with "now"); the form requires it via NotBlank.
        return $revision;
    }

    /**
     * Whether the activity described by this revision has already ended.
     */
    private function hasPassed(ActivityRevision $revision): bool
    {
        $endTime = $revision->getEndTime();

        return null !== $endTime && $endTime < new DateTime();
    }
}
