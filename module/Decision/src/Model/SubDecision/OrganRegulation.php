<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use DateTime;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class OrganRegulation extends SubDecision
{
    /**
     * Name of the organ.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Type of the organ.
     */
    #[Column(
        type: 'string',
        enumType: OrganTypes::class,
    )]
    protected OrganTypes $organType;

    /**
     * Version of the regulation.
     */
    #[Column(
        type: 'string',
        length: 32,
    )]
    protected string $version;

    /**
     * Date of the regulation.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * If the regulation was approved.
     */
    #[Column(type: 'boolean')]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    protected bool $changes;

    /**
     * Set the organ type
     */
    public function setOrganType(OrganTypes $organType): void
    {
        $this->organType = $organType;
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the version.
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Get the date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get approval status.
     */
    public function getApproval(): bool
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     */
    public function setApproval(bool $approval): void
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     */
    public function getChanges(): bool
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     */
    public function setChanges(bool $changes): void
    {
        $this->changes = $changes;
    }
}
