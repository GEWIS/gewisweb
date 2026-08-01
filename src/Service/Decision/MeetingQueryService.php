<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingPoint;
use App\Repository\Decision\MeetingDocumentRepository;
use App\Repository\Decision\MeetingReferenceSelectionRepository;
use App\Repository\Decision\MeetingRepository;
use App\ViewModel\Decision\DecisionListEntry;
use App\ViewModel\Decision\MeetingPointView;
use App\ViewModel\Decision\MeetingReadiness;
use App\ViewModel\Decision\MeetingStatus;
use App\ViewModel\Decision\MeetingView;
use App\ViewModel\Decision\NearbyMeeting;

use function array_count_values;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function spl_object_id;
use function trim;
use function usort;

/**
 * Assembles the read side of the meeting pages: the member view, the management readiness checklist, and the derived
 * meeting status.
 */
final readonly class MeetingQueryService
{
    public function __construct(
        private MeetingRepository $meetingRepository,
        private MeetingDocumentRepository $meetingDocumentRepository,
        private MeetingReferenceSelectionRepository $meetingReferenceSelectionRepository,
        private MeetingPointDecisionMatcher $matcher,
    ) {
    }

    public function getMeetingView(
        MeetingTypes $type,
        int $number,
    ): ?MeetingView {
        $meeting = $this->meetingRepository->findMeeting(
            $type,
            $number,
        );

        if (null === $meeting) {
            return null;
        }

        // Sort in PHP rather than trusting the collection's load-time order: a live action can have changed positions
        // in the same request, and the already-initialised collection would still be in its old order.
        $points = array_values($meeting->getPoints()->toArray());
        usort(
            $points,
            static fn (MeetingPoint $a, MeetingPoint $b): int => [
                $a->getDisplayPosition(),
                $a->getId(),
            ]
                <=> [
                    $b->getDisplayPosition(),
                    $b->getId(),
                ],
        );

        $decisions = array_values($meeting->getDecisions()->toArray());
        $references = $this->meetingReferenceSelectionRepository->findForMeeting($meeting);

        $documentsByPointId = [];
        $meetingLevelDocuments = [];
        $documentCount = count($references);
        foreach ($this->meetingDocumentRepository->findForMeeting($meeting) as $document) {
            $documentCount++;
            $point = $document->getPoint();

            if (null === $point) {
                $meetingLevelDocuments[] = $document;
                continue;
            }

            $documentsByPointId[(int) $point->getId()][] = $document;
        }

        $match = $this->matcher->match(
            $points,
            $decisions,
        );

        $pointViews = [];
        $pointByDecisionHash = [];
        foreach ($points as $point) {
            $matchedDecisions = $match->decisionsForPoint($point);
            $pointViews[] = new MeetingPointView(
                $point,
                $documentsByPointId[(int) $point->getId()] ?? [],
                $matchedDecisions,
            );

            foreach ($matchedDecisions as $decision) {
                $pointByDecisionHash[spl_object_id($decision)] = $point;
            }
        }

        $decisionEntries = [];
        foreach ($decisions as $decision) {
            $point = $pointByDecisionHash[spl_object_id($decision)] ?? null;
            $decisionEntries[] = new DecisionListEntry(
                $decision,
                $point,
                null === $point ? 0 : count($documentsByPointId[(int) $point->getId()] ?? []),
            );
        }

        return new MeetingView(
            $meeting,
            $this->getStatus($meeting),
            $pointViews,
            $meetingLevelDocuments,
            $decisionEntries,
            $match->unmatched,
            $references,
            $meeting->getMinutes(),
            $meeting->getLocalDetails(),
            $documentCount,
        );
    }

    /**
     * A meeting is complete once decisions have been synced or minutes have been uploaded; between the meeting date
     * and that moment it is shown as "being processed".
     */
    public function getStatus(Meeting $meeting): MeetingStatus
    {
        return MeetingStatus::derive(
            $meeting->getDate(),
            !$meeting->getDecisions()->isEmpty(),
            null !== $meeting->getMinutes()?->getLatestVersion(),
        );
    }

    public function getReadiness(MeetingView $view): MeetingReadiness
    {
        $numbers = array_map(
            static fn (MeetingPointView $pointView): string => trim($pointView->point->getNumber()),
            $view->points,
        );
        $duplicates = array_keys(array_filter(
            array_count_values($numbers),
            static fn (int $occurrences): bool => $occurrences > 1,
        ));

        $details = $view->localDetails;

        return new MeetingReadiness(
            count($view->points),
            $view->documentCount - count($view->references),
            count($view->references),
            null !== $view->minutes?->getLatestVersion(),
            null !== $details && (null !== $details->getStartTime() || null !== $details->getLocation()),
            $duplicates,
            count($view->unmatchedDecisions),
        );
    }

    /**
     * @return list<NearbyMeeting>
     */
    public function getNearbyMeetings(Meeting $meeting): array
    {
        return array_map(
            static fn (array $row): NearbyMeeting => new NearbyMeeting(
                $row['type'],
                $row['number'],
                $row['date'],
            ),
            $this->meetingRepository->findNearby($meeting),
        );
    }
}
