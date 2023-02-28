<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Key\Granting as KeyGranting;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    OneToOne,
};

/**
 * keyholder entity.
 *
 * Note that this entity is derived from the decisions themselves.
 */
#[Entity]
class Keyholder
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Member lidnr.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: "keyGrantings",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected Member $member;

    /**
     * Expiration date.
     */
    #[Column(type: "date")]
    protected DateTime $expirationDate;

    /**
     * Installation.
     */
    #[OneToOne(
        targetEntity: KeyGranting::class,
        inversedBy: "keyholder",
    )]
    #[JoinColumn(
        name: "r_meeting_type",
        referencedColumnName: "meeting_type",
    )]
    #[JoinColumn(
        name: "r_meeting_number",
        referencedColumnName: "meeting_number",
    )]
    #[JoinColumn(
        name: "r_decision_point",
        referencedColumnName: "decision_point",
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "decision_number",
    )]
    #[JoinColumn(
        name: "r_number",
        referencedColumnName: "number",
    )]
    protected KeyGranting $grantingDec;

    /**
     * Release date.
     */
    #[Column(
        type: "date",
        nullable: true,
    )]
    protected ?DateTime $withdrawnDate = null;

    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the expiration date.
     *
     * @return DateTime
     */
    public function getExpirationDate(): DateTime
    {
        return $this->expirationDate;
    }

    /**
     * Set the expiration date.
     *
     * @param DateTime $expirationDate
     */
    public function setExpirationDate(DateTime $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * Get the granting decision.
     *
     * @return KeyGranting
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
     *
     * @return DateTime|null
     */
    public function getWithdrawnDate(): ?DateTime
    {
        return $this->withdrawnDate;
    }

    /**
     * Set the withdrawn date.
     *
     * @param DateTime|null $withdrawnDate
     */
    public function setWithdrawnDate(?DateTime $withdrawnDate): void
    {
        $this->withdrawnDate = $withdrawnDate;
    }
}
