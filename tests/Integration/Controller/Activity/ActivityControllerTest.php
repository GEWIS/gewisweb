<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Activity;

use App\Controller\Activity\ActivityController;
use App\Entity\Activity\Activity;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Decision\AssociationYear;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * The public activity pages, invoked directly (the codebase has no WebTestCase). The archive folds into the overview
 * via a required year, and the cross-year search is its own page reusing the overview component.
 */
final class ActivityControllerTest extends DatabaseTestCase
{
    public function testIndexRendersTheUpcomingOverview(): void
    {
        $this->pushRequest();

        self::assertSame(
            Response::HTTP_OK,
            $this->controller()->index()->getStatusCode(),
        );
    }

    public function testArchiveRendersForTheCurrentAssociationYear(): void
    {
        $this->pushRequest();

        // The current year's archive lists finished activities only (past-only), but the page still renders.
        $year = AssociationYear::fromDate(new DateTime())->getYear();

        self::assertSame(
            Response::HTTP_OK,
            $this->controller()->archive($year)->getStatusCode(),
        );
    }

    public function testSearchRendersTheCrossYearSearchPage(): void
    {
        $this->pushRequest();

        $response = $this->controller()->search();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'activity-overview-search',
            (string) $response->getContent(),
        );
    }

    public function testViewShowsTheMyFutureLogoForCareerActivities(): void
    {
        $this->pushRequest();

        $response = $this->controller()->view($this->approvedActivityId(true));

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'myfuture.tue.nl',
            (string) $response->getContent(),
        );
    }

    public function testViewOmitsTheMyFutureLogoForOtherCategories(): void
    {
        $this->pushRequest();

        self::assertStringNotContainsString(
            'myfuture.tue.nl',
            (string) $this->controller()->view($this->approvedActivityId(false))->getContent(),
        );
    }

    private function controller(): ActivityController
    {
        return self::getContainer()->get(ActivityController::class);
    }

    private function approvedActivityId(bool $career): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->andWhere('a.unpublishedAt IS NULL')
            ->setParameter(
                'career',
                ActivityCategories::Career->value,
            )
            ->setMaxResults(1);

        $queryBuilder->andWhere(
            $career
                ? 'lr.category = :career'
                : 'lr.category != :career',
        );

        $activity = $queryBuilder->getQuery()->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
        );

        return (int) $activity->getId();
    }

    private function pushRequest(): void
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
}
