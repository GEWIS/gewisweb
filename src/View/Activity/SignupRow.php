<?php

declare(strict_types=1);

namespace App\View\Activity;

/**
 * A single row in the read-only "current subscriptions" table of a sign-up list.
 */
final readonly class SignupRow
{
    /**
     * @param list<array{hidden: bool, value: string}> $cells one cell per sign-up field, already formatted; `hidden`
     * marks a sensitive field that is not the viewer's own
     */
    public function __construct(
        public int $position,
        public string $fullName,
        public array $cells,
    ) {
    }
}
