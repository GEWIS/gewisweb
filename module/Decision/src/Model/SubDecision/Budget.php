<?php

namespace Decision\Model\SubDecision;

use DateTime;
use Decision\Model\{
    Member,
    SubDecision,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
};

/**
 * Budget decision.
 */
#[Entity]
class Budget extends SubDecision
{
    /**
     * Budget author.
     */
    #[ManyToOne(targetEntity: "Decision\Model\Member")]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
    )]
    protected Member $author;

    /**
     * Name of the budget.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Version of the budget.
     */
    #[Column(
        type: "string",
        length: 32,
    )]
    protected string $version;

    /**
     * Date of the budget.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * If the budget was approved.
     */
    #[Column(type: "boolean")]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: "boolean")]
    protected bool $changes;

    /**
     * Get the author.
     *
     * @return Member
     */
    public function getAuthor(): Member
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param Member $author
     */
    public function setAuthor(Member $author): void
    {
        $this->author = $author;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the version.
     *
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get approval status.
     *
     * @return bool
     */
    public function getApproval(): bool
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     *
     * @param bool $approval
     */
    public function setApproval(bool $approval): void
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     *
     * @return bool
     */
    public function getChanges(): bool
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     *
     * @param bool $changes
     */
    public function setChanges(bool $changes): void
    {
        $this->changes = $changes;
    }
}
