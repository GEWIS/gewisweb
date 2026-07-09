<?php

declare(strict_types=1);

namespace App\ViewModel\Activity\Admin;

use DateTime;

/**
 * A single subscriber row in the admin sign-ups table. This is the privileged superset of
 * {@see \App\ViewModel\Activity\SignupRow}: it carries the contact details, membership type, attendance/admission
 * flags and every field answer (no sensitive-field masking, as this screen is organiser/board-only).
 */
final readonly class SignupAdminRow
{
    /**
     * @param list<array{value: string}> $cells one per sign-up field, already formatted and in field order
     */
    public function __construct(
        public int $signupId,
        public int $position,
        public string $fullName,
        // "User (ordinary)" / "User (external)" for a member, "External" for a non-member sign-up.
        public string $membershipTypeLabel,
        // The member's generation, or null for an external sign-up.
        public ?int $generation,
        public bool $external,
        public DateTime $signedUpAt,
        public bool $present,
        public bool $drawn,
        public array $cells,
    ) {
    }
}
