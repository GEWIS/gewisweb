<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingPoint;

/**
 * One agenda point on the meeting page: the point with its documents and the decisions matched to it.
 */
final readonly class MeetingPointView
{
    /**
     * @param list<MeetingDocument> $documents
     * @param list<Decision>        $decisions
     */
    public function __construct(
        public MeetingPoint $point,
        public array $documents,
        public array $decisions,
    ) {
    }
}
