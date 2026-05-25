<?php

declare(strict_types=1);

namespace App\Entity\Decision\Traits;

use App\Entity\Decision\Member;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

trait MemberAwareTrait
{
    /**
     * The member involved in this sub-decision.
     *
     * Not all sub-decisions require this, as such it is nullable. However, sub-decisions that need the guarantee that
     * this is not null or need to specify an inverse side cannot do so any more (or could never). For more information,
     * see {@link https://github.com/doctrine/orm/pull/10470}.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?Member $member = null;

    /**
     * Get the member.
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }
}
