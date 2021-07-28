<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping\{
    Column,
    DiscriminatorColumn,
    DiscriminatorMap,
    Entity,
    Id,
    InheritanceType,
    JoinColumn,
    ManyToOne,
};

/**
 * SubDecision model.
 */
#[Entity]
#[InheritanceType(value: "SINGLE_TABLE")]
#[DiscriminatorColumn(
    name: "type",
    type: "string",
)]
#[DiscriminatorMap(value:
    [
        "foundation" => "Decision\Model\SubDecision\Foundation",
        "abrogation" => "Decision\Model\SubDecision\Abrogation",
        "installation" => "Decision\Model\SubDecision\Installation",
        "discharge" => "Decision\Model\SubDecision\Discharge",
        "budget" => "Decision\Model\SubDecision\Budget",
        "reckoning" => "Decision\Model\SubDecision\Reckoning",
        "other" => "Decision\Model\SubDecision\Other",
        "destroy" => "Decision\Model\SubDecision\Destroy",
        "board_installation" => "Decision\Model\SubDecision\Board\Installation",
        "board_release" => "Decision\Model\SubDecision\Board\Release",
        "board_discharge" => "Decision\Model\SubDecision\Board\Discharge",
        "foundationreference" => "Decision\Model\SubDecision\FoundationReference",
    ]
)]
abstract class SubDecision
{
    /**
     * Decision.
     */
    #[ManyToOne(
        targetEntity: "Decision\Model\Decision",
        inversedBy: "subdecisions",
    )]
    #[JoinColumn(
        name: "meeting_type",
        referencedColumnName: "meeting_type",
    )]
    #[JoinColumn(
        name: "meeting_number",
        referencedColumnName: "meeting_number",
    )]
    #[JoinColumn(
        name: "decision_point",
        referencedColumnName: "point",
    )]
    #[JoinColumn(
        name: "decision_number",
        referencedColumnName: "number",
    )]
    protected Decision $decision;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: "string")]
    protected string $meeting_type;

    /**
     * Meeting number.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $meeting_number;

    /**
     * Decision point.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $decision_point;

    /**
     * Decision number.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $decision_number;

    /**
     * Sub decision number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $number;

    /**
     * Content.
     */
    #[Column(type: "text")]
    protected string $content;

    /**
     * Get the decision.
     *
     * @return Decision
     */
    public function getDecision(): Decision
    {
        return $this->decision;
    }

    /**
     * Set the decision.
     *
     * @param Decision $decision
     */
    public function setDecision(Decision $decision): void
    {
        $decision->addSubdecision($this);
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
    public function getMeetingType(): string
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getMeetingNumber(): int
    {
        return $this->meeting_number;
    }

    /**
     * Get the decision point number.
     *
     * @return int
     */
    public function getDecisionPoint(): int
    {
        return $this->decision_point;
    }

    /**
     * Get the decision number.
     *
     * @return int
     */
    public function getDecisionNumber(): int
    {
        return $this->number;
    }

    /**
     * Get the number.
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Set the number.
     *
     * @param int $number
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content.
     *
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
