<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision\Key;

use DateTime;
use Decision\Model\Keyholder;
use Decision\Model\Member;
use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class Granting extends SubDecision
{
    /**
     * The member who is granted a keycode of GEWIS.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    protected ?Member $grantee = null;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: 'date')]
    protected DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: 'granting',
    )]
    protected ?Withdrawal $withdrawal = null;

    /**
     * Keyholder reference.
     */
    #[OneToOne(
        targetEntity: Keyholder::class,
        mappedBy: 'grantingDec',
    )]
    protected Keyholder $keyholder;

    /**
     * Get the grantee.
     */
    public function getGrantee(): ?Member
    {
        return $this->grantee;
    }

    /**
     * Set the grantee.
     */
    public function setGrantee(Member $grantee): void
    {
        $this->grantee = $grantee;
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
