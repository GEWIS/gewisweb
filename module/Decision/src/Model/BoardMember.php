<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Board\Installation;
use Doctrine\ORM\Mapping as ORM;

/**
 * Board member entity.
 *
 * Note that this entity is derived from the decisions themself.
 *
 * @ORM\Entity
 */
class BoardMember
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
     * Member lidnr.
     *
     * @ORM\ManyToOne(targetEntity="Member", inversedBy="boardInstallations")
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
     * @ORM\OneToOne(targetEntity="Decision\Model\SubDecision\Board\Installation", inversedBy="boardMember")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *     @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *     @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *     @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *     @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $installationDec;

    /**
     * Discharge date.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $dischargeDate;

    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
    public function setMember($member)
    {
        $this->member = $member;
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
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction($function)
    {
        $this->function = $function;
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
     * Set the installation date.
     *
     * @param DateTime $installDate
     */
    public function setInstallDate($installDate)
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation decision.
     *
     * @return Installation
     */
    public function getInstallationDec()
    {
        return $this->installationDec;
    }

    /**
     * Set the installation decision.
     */
    public function setInstallationDec(Installation $installationDec)
    {
        $this->installationDec = $installationDec;
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

    /**
     * Set the discharge date.
     *
     * @param DateTime $dischargeDate
     */
    public function setDischargeDate($dischargeDate)
    {
        $this->dischargeDate = $dischargeDate;
    }
}
