<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Activity;

use App\Controller\Activity\AdminApprovalController;
use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\EditLock;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\User;
use App\Repository\Application\EditLockRepository;
use App\Service\Activity\ActivityRevisionCloner;
use App\Service\Application\EditLockService;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The review controller is thin glue over already-tested pieces (the workflow, {@see DraftDiscarder},
 * {@see EditLockService}, {@see \App\Form\Application\ReviewDecisionType}); these pin its own discard action, the
 * recovery path for a draft whose submit the workflow withholds.
 *
 * The actions are invoked directly with the current user set on the token storage, rather than over HTTP: the app's
 * session guard force-logs-out any session not backed by a managed-session row, so a synthetic browser session never
 * survives a request. The HTTP boundary itself (routing, CSRF, the decision form's button-to-transition mapping) is
 * therefore left to the browser-level suite, where a real login establishes a real managed session.
 */
final class AdminApprovalControllerTest extends DatabaseTestCase
{
    public function testDiscardRevertsAReEditToLiveAndPurgesTheEditLock(): void
    {
        $activity = $this->anApprovedActivityWithoutSignupLists();
        $live = $activity->getLiveRevision();
        self::assertInstanceOf(
            ActivityRevision::class,
            $live,
        );

        // A draft re-edit of the approved activity, with an edit lock held on the aggregate.
        $draft = $this->cloner()->cloneAsDraft($live);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();
        $draftId = (int) $draft->getId();
        $this->editLockService()->acquire(
            $activity,
            $this->user(8025),
        );

        $this->authenticate(['ROLE_BOARD']);
        $this->pushRequestWithSession();

        $response = $this->controller()->discard($draft);

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        // The activity is back on its live revision, the draft is gone ...
        self::assertSame(
            $live,
            $activity->getCurrentRevision(),
        );
        self::assertNull(
            $this->entityManager->getRepository(ActivityRevision::class)->find($draftId),
        );
        // ... and the now-meaningless edit lock was purged with it.
        self::assertNull(
            $this->editLocks()->findOneByResource(
                $activity->getResourceId(),
                (int) $activity->getId(),
            ),
        );
    }

    public function testDiscardIsRefusedWhenThereIsNoLiveVersionToRevertTo(): void
    {
        // A brand-new (never-approved) draft has nothing to revert to; discarding it would delete the whole activity,
        // which is deliberately left to the stale-draft cleanup. The controller refuses and keeps the draft.
        $draft = $this->aNeverApprovedDraft();
        $draftId = (int) $draft->getId();

        $this->authenticate(['ROLE_BOARD']);
        $session = $this->pushRequestWithSession();

        $response = $this->controller()->discard($draft);

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNotNull(
            $this->entityManager->getRepository(ActivityRevision::class)->find($draftId),
        );
        self::assertNotEmpty($session->getFlashBag()->peek(AlertTypes::Warning->value));
    }

    public function testDiscardIsDeniedForANonOwnerNonBoardMember(): void
    {
        $draft = $this->aNeverApprovedDraft();

        // 8027 is in KEUR, not the organising organ (GETÉST), and neither created the activity nor reviews it, so
        // editing it away is denied.
        $this->authenticate(
            ['ROLE_ACTIVE_MEMBER'],
            8027,
        );
        $this->pushRequestWithSession();

        $this->expectException(AccessDeniedException::class);
        $this->controller()->discard($draft);
    }

    private function controller(): AdminApprovalController
    {
        return self::getContainer()->get(AdminApprovalController::class);
    }

    private function cloner(): ActivityRevisionCloner
    {
        return self::getContainer()->get(ActivityRevisionCloner::class);
    }

    private function editLockService(): EditLockService
    {
        return self::getContainer()->get(EditLockService::class);
    }

    private function editLocks(): EditLockRepository
    {
        return $this->entityManager->getRepository(EditLock::class);
    }

    /**
     * @param string[] $roles
     */
    private function authenticate(
        array $roles,
        int $lidnr = 8025,
    ): void {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user($lidnr),
            'main',
            $roles,
        ));
    }

    /**
     * A request carrying a session must be on the stack for the controller's flash messages to land somewhere.
     */
    private function pushRequestWithSession(): FlashBagAwareSessionInterface
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        return $session;
    }

    private function user(int $lidnr): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    private function anApprovedActivityWithoutSignupLists(): Activity
    {
        $activity = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->where('a.currentRevision = a.liveRevision')
            ->andWhere('SIZE(lr.signupLists) = 0')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
            'The seed is expected to contain an approved activity without sign-up lists.',
        );

        return $activity;
    }

    private function aNeverApprovedDraft(): ActivityRevision
    {
        $draft = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(
                ActivityRevision::class,
                'r',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->where('r.status = :draft')
            ->andWhere('a.liveRevision IS NULL')
            ->andWhere('a.currentRevision = r')
            ->setParameter(
                'draft',
                RevisionStatus::Draft->value,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
            'The seed is expected to contain a never-approved draft activity.',
        );

        return $draft;
    }
}
