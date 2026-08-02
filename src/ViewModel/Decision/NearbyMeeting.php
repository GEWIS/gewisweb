<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use DateTime;

/**
 * A row in the "Other meetings" sidebar. Deliberately not a {@see \App\Entity\Decision\Meeting}: hydrating the
 * entity drags its one-to-one sides along, and the sidebar only links.
 */
final readonly class NearbyMeeting
{
    public function __construct(
        public MeetingTypes $type,
        public int $number,
        public DateTime $date,
    ) {
    }
}
