<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Activity;

use App\Controller\Activity\ActivityController;
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

    private function controller(): ActivityController
    {
        return self::getContainer()->get(ActivityController::class);
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
