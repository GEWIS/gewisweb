<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Organ member entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class OrganMember
{
    use IdentifiableTrait;

    /**
     * Organ.
     */
    #[ManyToOne(
        targetEntity: Organ::class,
        inversedBy: 'members',
    )]
    protected Organ $organ;

    /**
     * Member.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'organInstallations',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
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
        targetEntity: Installation::class,
        inversedBy: 'organMember',
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
    protected Installation $installation;

    /**
     * Discharge date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $dischargeDate = null;

    /**
     * Set the organ.
     */
    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * Get the organ.
     */
    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the member.
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the function.
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the function.
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the installation date.
     */
    public function setInstallDate(DateTime $installDate): void
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation date.
     */
    public function getInstallDate(): DateTime
    {
        return $this->installDate;
    }

    /**
     * Set the installation.
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
    }

    /**
     * Get the installation.
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the discharge date.
     */
    public function setDischargeDate(?DateTime $dischargeDate): void
    {
        $this->dischargeDate = $dischargeDate;
    }

    /**
     * Get the discharge date.
     */
    public function getDischargeDate(): ?DateTime
    {
        return $this->dischargeDate;
    }
}
