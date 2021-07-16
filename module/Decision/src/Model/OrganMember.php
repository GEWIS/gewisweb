<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping as ORM;

/**
 * Organ member entity.
 *
 * Note that this entity is derived from the decisions themself.
 *
 * @ORM\Entity
 */
class OrganMember
{
    /**
     * Id.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Organ.
     *
     * @ORM\ManyToOne(targetEntity="Organ", inversedBy="members")
     */
    protected $organ;

    /**
     * Member.
     *
     * @ORM\ManyToOne(targetEntity="Member", inversedBy="organInstallations")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Function.
     *
     * @ORM\Column(type="string")
     */
    protected $function;

    /**
     * Installation date.
     *
     * @ORM\Column(type="date")
     */
    protected $installDate;

    /**
     * Installation.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\SubDecision\Installation", inversedBy="organMember")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *     @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *     @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *     @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *     @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $installation;

    /**
     * Discharge date.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $dischargeDate;

    /**
     * Set the organ.
     */
    public function setOrgan(Organ $organ)
    {
        $this->organ = $organ;
    }

    /**
     * Get the organ.
     *
     * @return Organ
     */
    public function getOrgan()
    {
        return $this->organ;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
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
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction($function)
    {
        $this->function = $function;
    }

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
     * Set the installation date.
     */
    public function setInstallDate(DateTime $installDate)
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation date.
     *
     * @return DateTime
     */
    public function getInstallDate()
    {
        return $this->installDate;
    }

    /**
     * Set the installation.
     *
     * @param Installation $installation
     */
    public function setInstallation($installation)
    {
        $this->installation = $installation;
    }

    /**
     * Get the installation.
     *
     * @return Installation
     */
    public function getInstallation()
    {
        return $this->installation;
    }

    /**
     * Set the discharge date.
     */
    public function setDischargeDate(DateTime $dischargeDate)
    {
        $this->dischargeDate = $dischargeDate;
    }

    /**
     * Get the discharge date.
     *
     * @return DateTime
     */
    public function getDischargeDate()
    {
        return $this->dischargeDate;
    }
}
