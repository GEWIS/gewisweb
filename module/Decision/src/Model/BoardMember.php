<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Board\Installation as BoardInstallation;
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
 * Board member entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class BoardMember
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Member lidnr.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: "boardInstallations",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected Member $member;

    /**
     * Function.
     */
    #[Column(type: "string")]
    protected string $function;

    /**
     * Installation date.
     */
    #[Column(type: "date")]
    protected DateTime $installDate;

    /**
     * Installation.
     */
    #[OneToOne(
        targetEntity: BoardInstallation::class,
        inversedBy: "boardMember",
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
    protected BoardInstallation $installationDec;

    /**
     * Discharge date.
     */
    #[Column(
        type: "date",
        nullable: true,
    )]
    protected ?DateTime $dischargeDate;

    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId(): int
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
     * Get the function.
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the installation date.
     *
     * @return DateTime
     */
    public function getInstallDate(): DateTime
    {
        return $this->installDate;
    }

    /**
     * Set the installation date.
     *
     * @param DateTime $installDate
     */
    public function setInstallDate(DateTime $installDate): void
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation decision.
     *
     * @return BoardInstallation
     */
    public function getInstallationDec(): BoardInstallation
    {
        return $this->installationDec;
    }

    /**
     * Set the installation decision.
     */
    public function setInstallationDec(BoardInstallation $installationDec): void
    {
        $this->installationDec = $installationDec;
    }

    /**
     * Get the discharge date.
     *
     * @return DateTime|null
     */
    public function getDischargeDate(): ?DateTime
    {
        return $this->dischargeDate;
    }

    /**
     * Set the discharge date.
     *
     * @param DateTime $dischargeDate
     */
    public function setDischargeDate(DateTime $dischargeDate): void
    {
        $this->dischargeDate = $dischargeDate;
    }
}
