<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Meeting;

/**
 * One row of the meetings overview table.
 */
final readonly class MeetingOverviewRow
{
    public function __construct(
        public Meeting $meeting,
        public int $decisionCount,
        public bool $hasMinutes,
        public MeetingStatus $status,
    ) {
    }
}
