<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

/**
 * The management-page checklist of a meeting, including the warning signals for a shifted agenda.
 */
final readonly class MeetingReadiness
{
    /**
     * @param list<string> $duplicatePointNumbers
     */
    public function __construct(
        public int $pointCount,
        public int $documentCount,
        public int $referenceCount,
        public bool $minutesUploaded,
        public bool $detailsSet,
        public array $duplicatePointNumbers,
        public int $unmatchedDecisionCount,
    ) {
    }
}
