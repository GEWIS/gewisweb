<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Activity;

use App\Controller\Activity\AdminController;
use App\Entity\Activity\Activity;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The board's cancel / un-cancel and unpublish / re-publish lifecycle actions on an approved activity. Like
 * {@see AdminApprovalControllerTest}, the actions are invoked directly (the app's session guard force-logs-out any
 * synthetic browser session), so this pins the in-method guards and state changes; the {@code #[IsGranted]} board gate
 * and CSRF are enforced by the framework at the HTTP boundary and covered by the browser-level suite.
 */
final class AdminControllerTest extends DatabaseTestCase
{
    public function testCancelAndUncancelAnApprovedActivity(): void
    {
        $activity = $this->anApprovedActivity();
        self::assertFalse($activity->isCancelled());

        $this->authenticate();
        $this->pushRequestWithSession();

        $response = $this->controller()->cancel(
            $this->user(8025),
            $activity,
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertTrue($activity->isCancelled());
        self::assertTrue($activity->isFrozen());
        self::assertNotNull($activity->getCancelledBy());

        $this->controller()->uncancel($activity);

        self::assertFalse($activity->isCancelled());
        self::assertFalse($activity->isFrozen());
        self::assertNull($activity->getCancelledBy());
    }

    public function testUnpublishAndRepublishAnApprovedActivity(): void
    {
        $activity = $this->anApprovedActivity();
        self::assertFalse($activity->isUnpublished());

        $this->authenticate();
        $this->pushRequestWithSession();

        $this->controller()->unpublish(
            $this->user(8025),
            $activity,
        );

        self::assertTrue($activity->isUnpublished());
        self::assertTrue($activity->isFrozen());
        self::assertNotNull($activity->getUnpublishedBy());

        $this->controller()->republish($activity);

        self::assertFalse($activity->isUnpublished());
        self::assertFalse($activity->isFrozen());
        self::assertNull($activity->getUnpublishedBy());
    }

    public function testCancelIsRefusedForAnActivityWithoutALiveRevision(): void
    {
        $activity = $this->aNeverApprovedActivity();

        $this->authenticate();
        $this->pushRequestWithSession();

        $this->controller()->cancel(
            $this->user(8025),
            $activity,
        );

        // No live revision means there is nothing published to cancel; the guard refuses and leaves it untouched.
        self::assertFalse($activity->isCancelled());
    }

    public function testUncancelIsRefusedWhenNotCancelled(): void
    {
        $activity = $this->anApprovedActivity();

        $this->authenticate();
        $this->pushRequestWithSession();

        $this->controller()->uncancel($activity);

        self::assertFalse($activity->isCancelled());
    }

    private function controller(): AdminController
    {
        return self::getContainer()->get(AdminController::class);
    }

    private function authenticate(int $lidnr = 8025): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user($lidnr),
            'main',
            ['ROLE_BOARD'],
        ));
    }

    private function pushRequestWithSession(): void
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
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

    private function anApprovedActivity(): Activity
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
            ->where('a.cancelledAt IS NULL')
            ->andWhere('a.unpublishedAt IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
            'The seed is expected to contain an approved activity.',
        );

        return $activity;
    }

    private function aNeverApprovedActivity(): Activity
    {
        $activity = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->where('a.liveRevision IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
            'The seed is expected to contain a never-approved activity.',
        );

        return $activity;
    }
}
