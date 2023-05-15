<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision;

use Decision\Model\{
    Organ,
    SubDecision,
};
use Decision\Model\Enums\OrganTypes;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    OneToMany,
    OneToOne,
};

/**
 * Foundation of an organ.
 */
#[Entity]
class Foundation extends SubDecision
{
    /**
     * Abbreviation (only for when organs are created).
     */
    #[Column(type: "string")]
    protected string $abbr;

    /**
     * Name (only for when organs are created).
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Type of the organ.
     */
    #[Column(
        type: "string",
        enumType: OrganTypes::class,
    )]
    protected OrganTypes $organType;

    /**
     * References from other subdecisions to this organ.
     */
    #[OneToMany(
        targetEntity: FoundationReference::class,
        mappedBy: "foundation",
    )]
    protected Collection $references;

    /**
     * Organ entry for this organ.
     */
    #[OneToOne(
        targetEntity: Organ::class,
        mappedBy: "foundation",
    )]
    protected Organ $organ;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

    /**
     * Get the abbreviation.
     *
     * @return string
     */
    public function getAbbr(): string
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     *
     * @param string $abbr
     */
    public function setAbbr(string $abbr): void
    {
        $this->abbr = $abbr;
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
     * Get the type.
     *
     * @return OrganTypes
     */
    public function getOrganType(): OrganTypes
    {
        return $this->organType;
    }

    /**
     * Set the type.
     *
     * @param OrganTypes $organType
     */
    public function setOrganType(OrganTypes $organType): void
    {
        $this->organType = $organType;
    }

    /**
     * Get the references.
     *
     * @return Collection of references
     */
    public function getReferences(): Collection
    {
        return $this->references;
    }

    /**
     * Get the referenced organ.
     *
     * @return Organ
     */
    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    /**
     * Get a unique identifier for this foundation. It is used to distinguish between organs that share the same name
     * but are actually distinct.
     */
    public function getHash(): string
    {
        return sprintf(
            '%s-%d.%d.%d.%d',
            $this->getMeetingType()->value,
            $this->getMeetingNumber(),
            $this->getDecisionPoint(),
            $this->getDecisionNumber(),
            $this->getNumber(),
        );
    }

    /**
     * Get an array with all information.
     *
     * Mostly usefull for usage with JSON.
     *
     * @return array
     */
    public function toArray(): array
    {
        $decision = $this->getDecision();

        return [
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'decision_point' => $decision->getPoint(),
            'decision_number' => $decision->getNumber(),
            'subdecision_number' => $this->getNumber(),
            'abbr' => $this->getAbbr(),
            'name' => $this->getName(),
            'organtype' => $this->getOrganType(),
        ];
    }
}
