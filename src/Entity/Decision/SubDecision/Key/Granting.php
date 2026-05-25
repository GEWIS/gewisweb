<?php

declare(strict_types=1);

namespace App\Entity\Decision\SubDecision\Key;

use App\Entity\Decision\Keyholder;
use App\Entity\Decision\Member;
use App\Entity\Decision\SubDecision;
use App\Entity\Decision\Traits\MemberAwareTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class Granting extends SubDecision
{
    use MemberAwareTrait;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: 'granting',
    )]
    private ?Withdrawal $withdrawal = null;

    /**
     * Keyholder reference.
     */
    #[OneToOne(
        targetEntity: Keyholder::class,
        mappedBy: 'grantingDec',
    )]
    private Keyholder $keyholder;

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Get the date.
     */
    public function getUntil(): DateTime
    {
        return $this->until;
    }

    /**
     * Set the date.
     */
    public function setUntil(DateTime $until): void
    {
        $this->until = $until;
    }

    /**
     * Get the withdrawal decision.
     */
    public function getWithdrawal(): ?Withdrawal
    {
        return $this->withdrawal;
    }

    /**
     * Clears the withdrawal, if it exists.
     */
    public function clearWithdrawal(): void
    {
        $this->withdrawal = null;
    }

    /**
     * Get the keyholder decision.
     */
    public function getKeyholder(): Keyholder
    {
        return $this->keyholder;
    }
}
