<?php

declare(strict_types=1);

namespace App\ViewModel\Activity;

use App\Entity\Activity\Enums\ProposalLimitSource;

use function max;

/**
 * How many activities a body may still put forward in one option period, and which rule said so.
 *
 * The rule is carried alongside the number because a body that is told it may propose no more wants to know who
 * decided that. The old calendar answered zero with no explanation, and zero was usually nobody's decision at all.
 */
final readonly class ProposalAllowance
{
    public function __construct(
        public int $maximum,
        public int $used,
        public ProposalLimitSource $source,
    ) {
    }

    public function remaining(): int
    {
        return max(
            0,
            $this->maximum - $this->used,
        );
    }

    public function isExhausted(): bool
    {
        return 0 === $this->remaining();
    }
}
