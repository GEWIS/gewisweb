<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingMinutes;
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
 * Pins the assembled meeting view against the seed. The GMM with minutes ("complete") carries the documents, the
 * pinned reference, and the decisions that exercise the matching: exact points, the lettered "7a"/"7b" pair, and one
 * decision without an agenda point. The GMM after it is still being processed, and the soonest upcoming GMM has the
 * local time and place. GMM numbers are sequential in date order, so neighbours resolve by offset.
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
        $view = $this->view($this->completeGmmNumber());

        self::assertSame(
            MeetingStatus::Complete,
            $view->status,
        );
        self::assertCount(
            4,
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
        self::assertSame(
            'v3.0',
            $reference->getPinnedVersion()->getVersionLabel(),
        );

        self::assertSame(
            'v1.1',
            $view->minutes?->getLatestVersion()?->getVersionLabel(),
        );

        // Four documents plus one reference selection.
        self::assertSame(
            5,
            $view->documentCount,
        );
    }

    public function testMeetingWithoutDecisionsOrMinutesIsStillBeingProcessed(): void
    {
        $view = $this->view($this->completeGmmNumber() + 1);

        self::assertSame(
            MeetingStatus::HeldProcessing,
            $view->status,
        );

        $reference = $view->references[0];
        self::assertSame(
            'v3.1',
            $reference->getPinnedVersion()->getVersionLabel(),
        );
    }

    public function testSeededDecisionsAttachToExactAndFirstLetteredPoints(): void
    {
        $view = $this->view($this->completeGmmNumber());

        // Points "2" and "3" get their decisions, the first lettered variant "7a" gets the point 7 decision,
        // "7b" nothing, and the point 5 decision matches no agenda point at all.
        self::assertSame(
            [
                1,
                1,
                1,
                0,
            ],
            array_map(
                static fn (MeetingPointView $pointView) => count($pointView->decisions),
                $view->points,
            ),
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
        self::assertTrue($readiness->minutesUploaded);
    }

    public function testUpcomingMeetingStatusAndNearbyMeetings(): void
    {
        $upcomingNumber = $this->completeGmmNumber() + 2;
        $view = $this->view($upcomingNumber);

        self::assertSame(
            MeetingStatus::Upcoming,
            $view->status,
        );
        self::assertSame(
            'Auditorium 4',
            $view->localDetails?->getLocation(),
        );

        $nearby = array_map(
            static fn (NearbyMeeting $row) => $row->number,
            $this->queryService->getNearbyMeetings($view->meeting),
        );
        self::assertCount(
            4,
            $nearby,
        );
        self::assertGreaterThan(
            $upcomingNumber,
            $nearby[0],
        );
        self::assertLessThan(
            $upcomingNumber,
            $nearby[3],
        );
    }

    public function testNearbyMeetingsShowTwoAfterAndTwoBefore(): void
    {
        $completeNumber = $this->completeGmmNumber();
        $view = $this->view($completeNumber);

        self::assertSame(
            [
                $completeNumber + 2,
                $completeNumber + 1,
                $completeNumber - 1,
                $completeNumber - 2,
            ],
            array_map(
                static fn (NearbyMeeting $row) => $row->number,
                $this->queryService->getNearbyMeetings($view->meeting),
            ),
        );
    }

    private function view(int $number): MeetingView
    {
        $view = $this->queryService->getMeetingView(
            MeetingTypes::ALV,
            $number,
        );
        self::assertNotNull($view);

        return $view;
    }

    /**
     * The number of the one GMM that has minutes; the calendar is seeded around "today", so it moves with the run
     * date.
     */
    private function completeGmmNumber(): int
    {
        $minutes = $this->entityManager->getRepository(MeetingMinutes::class)->findAll();
        self::assertCount(
            1,
            $minutes,
        );

        return $minutes[0]->getMeeting()->getNumber();
    }
}
