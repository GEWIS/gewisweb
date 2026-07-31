<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\MeetingPoint;

/**
 * One decision in the full decision list of a meeting, with the agenda point it was matched to (if any) so the list
 * can link back to the documents of that point.
 */
final readonly class DecisionListEntry
{
    public function __construct(
        public Decision $decision,
        public ?MeetingPoint $point,
        public int $documentCount,
    ) {
    }
}
