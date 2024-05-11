<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\SubDecision\Board\Installation as BoardInstallation;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Board member entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class BoardMember
{
    use IdentifiableTrait;

    /**
     * Member lidnr.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'boardInstallations',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected Member $member;

    /**
     * Function.
     */
    #[Column(type: 'string')]
    protected string $function;

    /**
     * Installation date.
     */
    #[Column(type: 'date')]
    protected DateTime $installDate;

    /**
     * Installation.
     */
    #[OneToOne(
        targetEntity: BoardInstallation::class,
        inversedBy: 'boardMember',
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
    protected BoardInstallation $installationDec;

    /**
     * Release date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $releaseDate = null;

    /**
     * Discharge date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $dischargeDate = null;

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
     * Get the function.
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the installation date.
     */
    public function getInstallDate(): DateTime
    {
        return $this->installDate;
    }

    /**
     * Set the installation date.
     */
    public function setInstallDate(DateTime $installDate): void
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation decision.
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
     * Get the release date.
     */
    public function getReleaseDate(): ?DateTime
    {
        return $this->releaseDate;
    }

    /**
     * Set the release date.
     */
    public function setReleaseDate(?DateTime $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    /**
     * Get the discharge date.
     */
    public function getDischargeDate(): ?DateTime
    {
        return $this->dischargeDate;
    }

    /**
     * Set the discharge date.
     */
    public function setDischargeDate(?DateTime $dischargeDate): void
    {
        $this->dischargeDate = $dischargeDate;
    }
}
