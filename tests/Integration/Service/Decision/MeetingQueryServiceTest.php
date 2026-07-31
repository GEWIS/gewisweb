<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingReferenceSelection;
use App\Service\Decision\MeetingPointDecisionMatcher;
use App\Service\Decision\MeetingQueryService;
use App\Tests\Integration\DatabaseTestCase;
use App\ViewModel\Decision\MeetingPointView;
use App\ViewModel\Decision\MeetingStatus;
use App\ViewModel\Decision\MeetingView;
use App\ViewModel\Decision\NearbyMeeting;
use Override;

use function array_map;
use function count;

/**
 * Pins the assembled meeting view against the seed: ALV-0 is complete through its minutes and pins a reference
 * version, ALV-1 is still being processed, BV-0 exercises the decision matching against real seeded decisions, and
 * BV-1 has a decision that matches no agenda point.
 */
final class MeetingQueryServiceTest extends DatabaseTestCase
{
    private MeetingQueryService $queryService;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The service is not public and nothing references it yet, so the compiled container has inlined it away.
        $this->queryService = new MeetingQueryService(
            $this->entityManager->getRepository(Meeting::class),
            $this->entityManager->getRepository(MeetingDocument::class),
            $this->entityManager->getRepository(MeetingReferenceSelection::class),
            new MeetingPointDecisionMatcher(),
        );
    }

    public function testCompletedMeetingViewCarriesDocumentsMinutesAndPinnedReference(): void
    {
        $view = $this->view(
            MeetingTypes::ALV,
            0,
        );

        self::assertSame(
            MeetingStatus::Complete,
            $view->status,
        );
        self::assertCount(
            3,
            $view->points,
        );

        $agenda = $view->points[0]->documents[0];
        self::assertSame(
            'Agenda',
            $agenda->getName(),
        );
        self::assertCount(
            2,
            $agenda->getVersions(),
        );
        self::assertSame(
            'v1.1',
            $agenda->getLatestVersion()?->getVersionLabel(),
        );

        $meetingLevelNames = array_map(
            static fn (MeetingDocument $document) => $document->getName(),
            $view->meetingLevelDocuments,
        );
        self::assertSame(
            ['Letter to the GMM'],
            $meetingLevelNames,
        );

        self::assertCount(
            1,
            $view->references,
        );
        $reference = $view->references[0];
        self::assertNotNull($reference->getPinnedVersion());
        self::assertSame(
            'v3.0',
            $reference->getEffectiveVersion()?->getVersionLabel(),
        );

        self::assertSame(
            'v1.1',
            $view->minutes?->getLatestVersion()?->getVersionLabel(),
        );

        // Three documents plus one reference selection.
        self::assertSame(
            4,
            $view->documentCount,
        );
    }

    public function testMeetingWithoutDecisionsOrMinutesIsStillBeingProcessed(): void
    {
        $view = $this->view(
            MeetingTypes::ALV,
            1,
        );

        self::assertSame(
            MeetingStatus::HeldProcessing,
            $view->status,
        );

        $reference = $view->references[0];
        self::assertNull($reference->getPinnedVersion());
        self::assertSame(
            'v3.1',
            $reference->getEffectiveVersion()?->getVersionLabel(),
        );
    }

    public function testSeededDecisionsAttachToExactAndFirstLetteredPoints(): void
    {
        $view = $this->view(
            MeetingTypes::BV,
            0,
        );

        self::assertSame(
            MeetingStatus::Complete,
            $view->status,
        );
        self::assertSame(
            [],
            $view->unmatchedDecisions,
        );

        // The exact "1" gets its decision, the first lettered variant "2a" gets the point 2 decision, "2b" nothing.
        self::assertSame(
            [
                1,
                1,
                0,
            ],
            array_map(
                static fn (MeetingPointView $pointView) => count($pointView->decisions),
                $view->points,
            ),
        );
    }

    public function testDecisionMatchingNoPointCountsTowardsTheReadinessWarning(): void
    {
        $view = $this->view(
            MeetingTypes::BV,
            1,
        );

        self::assertCount(
            1,
            $view->unmatchedDecisions,
        );

        $readiness = $this->queryService->getReadiness($view);
        self::assertSame(
            1,
            $readiness->unmatchedDecisionCount,
        );
        self::assertSame(
            [],
            $readiness->duplicatePointNumbers,
        );
        self::assertFalse($readiness->minutesUploaded);
    }

    public function testUpcomingMeetingStatusAndNearbyMeetings(): void
    {
        $view = $this->view(
            MeetingTypes::ALV,
            3,
        );

        self::assertSame(
            MeetingStatus::Upcoming,
            $view->status,
        );
        self::assertSame(
            'Auditorium 4',
            $view->localDetails?->getLocation(),
        );

        $nearby = $this->queryService->getNearbyMeetings($view->meeting);
        self::assertSame(
            [
                2,
                1,
                0,
            ],
            array_map(
                static fn (NearbyMeeting $row) => $row->number,
                $nearby,
            ),
        );
    }

    public function testNearbyMeetingsAlwaysIncludeFollowingOnes(): void
    {
        $view = $this->view(
            MeetingTypes::ALV,
            1,
        );

        // Two after and two before where available; ALV-1 only has ALV-0 before it, so a third later one fills in.
        self::assertSame(
            [
                3,
                2,
                0,
            ],
            array_map(
                static fn (NearbyMeeting $row) => $row->number,
                $this->queryService->getNearbyMeetings($view->meeting),
            ),
        );
    }

    private function view(
        MeetingTypes $type,
        int $number,
    ): MeetingView {
        $view = $this->queryService->getMeetingView(
            $type,
            $number,
        );
        self::assertNotNull($view);

        return $view;
    }
}
