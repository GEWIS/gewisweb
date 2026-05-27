<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\SubDecision\Key\Granting as KeyGranting;
use App\Repository\Decision\KeyholderRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * keyholder entity.
 *
 * Note that this entity is derived from the decisions themselves.
 */
#[Entity(repositoryClass: KeyholderRepository::class)]
class Keyholder
{
    use IdentifiableTrait;

    /**
     * Member lidnr.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'keyGrantings',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private Member $member;

    /**
     * Expiration date.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $expirationDate;

    /**
     * Installation.
     */
    #[OneToOne(
        targetEntity: KeyGranting::class,
        inversedBy: 'keyholder',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_sequence',
        referencedColumnName: 'sequence',
    )]
    private KeyGranting $grantingDec;

    /**
     * Release date.
     */
    #[Column(
        type: Types::DATE_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $withdrawnDate = null;

    /**
     * Get the member.
     */
    public function getMember(): Member
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

    /**
     * Get the expiration date.
     */
    public function getExpirationDate(): DateTime
    {
        return $this->expirationDate;
    }

    /**
     * Set the expiration date.
     */
    public function setExpirationDate(DateTime $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * Get the granting decision.
     */
    public function getGrantingDec(): KeyGranting
    {
        return $this->grantingDec;
    }

    /**
     * Set the granting decision.
     */
    public function setGrantingDec(KeyGranting $grantingDec): void
    {
        $this->grantingDec = $grantingDec;
    }

    /**
     * Get the withdrawn date.
     */
    public function getWithdrawnDate(): ?DateTime
    {
        return $this->withdrawnDate;
    }

    /**
     * Set the withdrawn date.
     */
    public function setWithdrawnDate(?DateTime $withdrawnDate): void
    {
        $this->withdrawnDate = $withdrawnDate;
    }

    /**
     * Get whether the key decision is still valid
     */
    public function isCurrent(): bool
    {
        $now = new DateTime('today');

        return $this->getExpirationDate() >= $now
            && (
                null === $this->getWithdrawnDate()
                || $this->getWithdrawnDate() >= $now
            );
    }
}
