<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\MeetingPoint;

/**
 * The outcome of matching a meeting's decisions to its agenda points.
 */
final readonly class DecisionMatchResult
{
    /**
     * @param array<int, list<Decision>> $byPointId decisions per meeting point id
     * @param list<Decision>             $unmatched decisions whose point matches no agenda point
     */
    public function __construct(
        public array $byPointId,
        public array $unmatched,
    ) {
    }

    /**
     * @return list<Decision>
     */
    public function decisionsForPoint(MeetingPoint $point): array
    {
        return $this->byPointId[(int) $point->getId()] ?? [];
    }
}
