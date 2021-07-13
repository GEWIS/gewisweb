<?php

namespace Decision\Model\SubDecision;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Decision\Model\SubDecision;
use Decision\Model\Member;

/**
 * Budget decision
 *
 * @ORM\Entity
 */
class Budget extends SubDecision
{
    /**
     * Budget author.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $author;

    /**
     * Name of the budget.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Version of the budget.
     *
     * @ORM\Column(type="string",length=32)
     */
    protected $version;

    /**
     * Date of the budget.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * If the budget was approved.
     *
     * @ORM\Column(type="boolean")
     */
    protected $approval;

    /**
     * If there were changes made.
     *
     * @ORM\Column(type="boolean")
     */
    protected $changes;

    /**
     * Get the author.
     *
     * @return Member
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param Member $author
     */
    public function setAuthor(Member $author)
    {
        $this->author = $author;
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
     * Get the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the version.
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the date.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Get approval status.
     *
     * @return bool
     */
    public function getApproval()
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     *
     * @param bool $approval
     */
    public function setApproval($approval)
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     *
     * @return bool
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     *
     * @param bool $changes
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;
    }
}
