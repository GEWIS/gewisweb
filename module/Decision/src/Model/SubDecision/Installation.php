<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use Decision\Model\Member;
use Decision\Model\OrganMember;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Installation into organ.
 */
#[Entity]
class Installation extends FoundationReference
{
    /**
     * Function given.
     */
    #[Column(type: 'string')]
    protected string $function;

    /**
     * Member.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'installations',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
    )]
    protected Member $member;

    /**
     * Reappointment subdecisions if this installation was prolonged (can be done multiple times).
     *
     * @var Collection<array-key, Reappointment>
     */
    #[OneToMany(
        targetEntity: Reappointment::class,
        mappedBy: 'installation',
    )]
    protected Collection $reappointments;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected Discharge $discharge;

    /**
     * The organmember reference.
     */
    #[OneToOne(
        targetEntity: OrganMember::class,
        mappedBy: 'installation',
    )]
    protected OrganMember $organMember;

    public function __construct()
    {
        $this->reappointments = new ArrayCollection();
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
     * Get the reappointments, if they exist.
     *
     * @return Collection<array-key, Reappointment>
     */
    public function getReappointments(): Collection
    {
        return $this->reappointments;
    }

    /**
     * Get the discharge, if it exists.
     */
    public function getDischarge(): Discharge
    {
        return $this->discharge;
    }

    /**
     * Get the organ member reference.
     */
    public function getOrganMember(): OrganMember
    {
        return $this->organMember;
    }
}
