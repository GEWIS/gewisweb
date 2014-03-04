<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubDecision model.
 *
 * @ORM\Entity
 */
class SubDecision
{

    /**
     * Decision.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Decision")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="decision_point", referencedColumnName="point"),
     *  @ORM\JoinColumn(name="decision_number", referencedColumnName="number"),
     * })
     */
    protected $decision;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $meeting_type;

    /**
     * Meeting number
     *
     * NOTE: This is a hack to make the decision a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $meeting_number;

    /**
     * Decision point.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $decision_point;

    /**
     * Decision number.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $decision_number;

    /**
     * Sub decision number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $number;

    /**
     * Referenced subdecision.
     *
     * We use this to reference to other subdecisions. This can be to revoke
     * them, or to reference a created organ.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\SubDecision")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *  @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $reference;

    /**
     * Abbreviation (only for when organs are created)
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $abbr;

    /**
     * Name (only for when organs are created)
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * Function given.
     *
     * Can only be one of:
     * - chairman
     * - treasurer
     * - secretary
     * - vice-chairman
     * - pr-officer
     * - education-officer
     *
     * @todo Determine values of this for historical reasons
     * @todo Create constants for this
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $function;

    /**
     * Member for which this subdecision is applicable
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Textual content for the decision.
     *
     * @ORM\Column(type="string")
     */
    protected $content;

    /**
     * Type of the decision.
     *
     * Can only be one of:
     * - create organ
     * - abrogation of an organ
     * - installing member
     * - discharging members
     * - releasing member of function (is not a discharge (yet)!)
     * - misc
     *
     * @todo Determine all values for this
     * @todo Create constants for this
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $type;


    /**
     * Get the decision.
     *
     * @return Decision
     */
    public function getDecision()
    {
        return $this->decision;
    }

    /**
     * Set the decision.
     *
     * @param Decision $decision
     */
    public function setDecision(Decision $decision)
    {
        $this->meeting_type = $decision->getMeetingType();
        $this->meeting_number = $decision->getMeetingNumber();
        $this->decision_point = $decision->getPoint();
        $this->decision_number = $decision->getNumber();
        $this->decision = $decision;
    }

    /**
     * Get the meeting type.
     *
     * @return string
     */
    public function getMeetingType()
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getMeetingNumber()
    {
        return $this->meeting_number;
    }

    /**
     * Get the decision point number.
     *
     * @return int
     */
    public function getDecisionPoint()
    {
        return $this->decision_point;
    }

    /**
     * Get the decision number.
     *
     * @return int
     */
    public function getDecisionNumber()
    {
        return $this->number;
    }

    /**
     * Get the number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set the number.
     *
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get the reference.
     *
     * @return SubDecision
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set the reference.
     *
     * @param Reference $reference
     */
    public function setReference(Reference $reference)
    {
        $this->reference = $reference;
    }

    /**
     * Get the abbreviation.
     *
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     *
     * @param string $abbr
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @todo Make sure that the function is of an allowed value
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
     * Get the content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @todo Make sure that the type is of an allowed value
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
