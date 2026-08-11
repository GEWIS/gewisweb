<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Member;

/**
 * One member of a body, with whatever they were installed as beyond simply being a member. The functions are already
 * named in the reader's language, the way {@see \App\Service\Decision\MemberInfoService} hands them over too.
 */
final readonly class OrganMembership
{
    /**
     * @param list<string> $functions
     */
    public function __construct(
        public Member $member,
        public array $functions = [],
    ) {
    }

    /**
     * @param list<string> $functions
     */
    public function withFunctions(array $functions): self
    {
        return new self(
            $this->member,
            $functions,
        );
    }
}
