<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * One boolean out of a set that is read together, such as the facilities an activity asks for. Kept as a set because
 * that is how a reader takes them in: a row of badges under one heading, not a row per switch.
 */
final readonly class RevisionFlag
{
    public function __construct(
        public TranslatableInterface $label,
        public ?bool $old,
        public bool $new,
    ) {
    }
}
