<?php

namespace Decision\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Decision\Model\SubDecision;
use Decision\Model\Member;

/**
 * Installation into organ.
 *
 * @ORM\Entity
 */
class Installation extends FoundationReference
{
    /**
     * Function given.
     *
     * @ORM\Column(type="string")
     */
    protected $function;

    /**
     * Member.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Member",inversedBy="installations")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Discharges.
     *
     * @ORM\OneToOne(targetEntity="Discharge",mappedBy="installation")
     */
    protected $discharge;

    /**
     * The organmember reference.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\OrganMember",mappedBy="installation")
     */
    protected $organMember;

    /**
     * Get the function.
     *
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction($function)
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
    }

    /**
     * Get the discharge, if it exists
     *
     * @return Discharge
     */
    public function getDischarge()
    {
        return $this->discharge;
    }

    /**
     * Get the organ member reference.
     */
    public function getOrganMember()
    {
        return $this->organMember;
    }
}
