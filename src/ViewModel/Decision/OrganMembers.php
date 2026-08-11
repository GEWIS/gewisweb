<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Member;

/**
 * Who is in a body, split the way a body's page shows them: the members doing the work, the ones installed as inactive,
 * and, for a body that has been abrogated, everybody who was ever in it.
 */
final readonly class OrganMembers
{
    /**
     * @param list<OrganMembership> $active
     * @param list<Member>          $inactive
     * @param list<Member>          $former
     */
    public function __construct(
        public array $active = [],
        public array $inactive = [],
        public array $former = [],
    ) {
    }
}
