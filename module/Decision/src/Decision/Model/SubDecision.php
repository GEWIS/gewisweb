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
}
