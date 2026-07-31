<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingLocalDetails;
use App\Entity\Decision\MeetingMinutes;
use App\Entity\Decision\MeetingReferenceSelection;

/**
 * Everything the meeting page shows, assembled once by {@see \App\Service\Decision\MeetingQueryService}.
 */
final readonly class MeetingView
{
    /**
     * @param list<MeetingPointView>          $points
     * @param list<MeetingDocument>           $meetingLevelDocuments documents not filed under an agenda point
     * @param list<Decision>                  $decisions             all decisions, in point and number order
     * @param list<Decision>                  $unmatchedDecisions    decisions whose point matches no agenda point
     * @param list<MeetingReferenceSelection> $references
     */
    public function __construct(
        public Meeting $meeting,
        public MeetingStatus $status,
        public array $points,
        public array $meetingLevelDocuments,
        public array $decisions,
        public array $unmatchedDecisions,
        public array $references,
        public ?MeetingMinutes $minutes,
        public ?MeetingLocalDetails $localDetails,
        public int $documentCount,
    ) {
    }
}
