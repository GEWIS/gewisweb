<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

use DateTimeInterface;

/**
 * A start and an end, either of which may be open. What an open end means is the domain's business: a vacancy that
 * starts on approval has no start yet, an activity always has both.
 */
final readonly class RevisionDateRange
{
    public function __construct(
        public ?DateTimeInterface $start = null,
        public ?DateTimeInterface $end = null,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->timestamp($this->start) === $this->timestamp($other->start)
            && $this->timestamp($this->end) === $this->timestamp($other->end);
    }

    private function timestamp(?DateTimeInterface $moment): ?int
    {
        return $moment?->getTimestamp();
    }
}
