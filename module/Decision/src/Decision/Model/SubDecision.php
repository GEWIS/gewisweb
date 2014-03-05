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

    const FUNC_CHAIRMAN = 'chairman';
    const FUNC_SECRETARY = 'secretary';
    const FUNC_TREASURER = 'treasurer';
    const FUNC_VICE_CHAIRMAN = 'vice-chairman';
    const FUNC_PR_OFFICER = 'pr-officer';
    const FUNC_EDUCATION_OFFICER = 'education-officer';

    const TYPE_FOUNDATION = 'foundation'; // creation of organ
    const TYPE_ABROGATION = 'abrogation';
    const TYPE_INSTALLATION = 'installation';
    const TYPE_DISCHARGE = 'discharge';
    const TYPE_RELEASE = 'release';
    const TYPE_BUDGET = 'budget';
    const TYPE_RECKONING = 'reckoning';
    const TYPE_OTHER = 'other';

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
     * Referenced decision.
     *
     * We use this to reference to other decisions.
     *
     * Note that this reference always exsists if there also is a referenced
     * subdecision. But the opposite does not have to hold. r_number might be
     * null, thus then there only would be a reference to a decision.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Decision")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="number")
     * })
     */
    protected $decision_reference;

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
     * - secretary
     * - treasurer
     * - vice-chairman
     * - pr-officer
     * - education-officer
     *
     * @todo Determine values of this for historical reasons
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $function;

    /**
     * Possible function types.
     */
    protected static $functions = array(
        self::FUNC_CHAIRMAN,
        self::FUNC_SECRETARY,
        self::FUNC_TREASURER,
        self::FUNC_VICE_CHAIRMAN,
        self::FUNC_PR_OFFICER,
        self::FUNC_EDUCATION_OFFICER
    );

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
     * - foundation (of organ)
     * - abrogation (of organ)
     * - installation (member in organ)
     * - discharge (member in organ)
     * - releasing (member of function, is not a discharge (yet)!)
     * - budget
     * - reckoning
     * - other
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Possible types.
     */
    protected static $types = array(
        self::TYPE_FOUNDATION,
        self::TYPE_ABROGATION,
        self::TYPE_INSTALLATION,
        self::TYPE_DISCHARGE,
        self::TYPE_RELEASE,
        self::TYPE_BUDGET,
        self::TYPE_RECKONING,
        self::TYPE_OTHER
    );


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
     * Get the decision reference.
     *
     * @return Decision
     */
    public function getDecisionReference()
    {
        return $this->decision_reference;
    }

    /**
     * Set the decision reference.
     *
     * @param Decision $reference
     */
    public function setDecisionReference(Decision $reference)
    {
        $this->decision_reference = $reference;
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
     * @param SubDecision $reference
     */
    public function setReference(SubDecision $reference)
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
     * @param string $function
     *
     * @throws \IllegalArgumentException when a nonexisting function is given.
     */
    public function setFunction($function)
    {
        if (!in_array($function, self::$functions)) {
            throw \IllegalArgumentException("Nonexisting function given.");
        }
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
     * @param string $type
     *
     * @throws \IllegalArgumentException when a nonexisting function is given.
     */
    public function setType($type)
    {
        if (!in_array($type, self::$types)) {
            throw \IllegalArgumentException("Nonexisting type given.");
        }
        $this->type = $type;
    }
}
