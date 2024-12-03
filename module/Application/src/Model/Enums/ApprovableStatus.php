<?php

declare(strict_types=1);

namespace Application\Model\Enums;

/**
 * Enum for keeping track of the status of an approval. An approval can be either 'unapproved' (the default state),
 * 'approved', or 'rejected'.
 */
enum ApprovableStatus: int
{
    case Unapproved = 0;
    case Approved = 1;
    case Rejected = 2;

    public function getIcon(): string
    {
        return match ($this) {
            self::Unapproved => 'fa-circle-question',
            self::Approved => 'fa-circle-check',
            self::Rejected => 'fa-circle-xmark',
        };
    }
}
