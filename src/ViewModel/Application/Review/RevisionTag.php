<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

use App\Entity\Application\LocalisedText;

/**
 * One label out of a set, kept with its identity so a revision that reorders its labels does not read as having
 * changed them.
 */
final readonly class RevisionTag
{
    public function __construct(
        public ?int $id,
        public LocalisedText $label,
    ) {
    }
}
