<?php

namespace Decision\Model\SubDecision;

use Decision\Model\{
    Organ,
    SubDecision,
};
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    OneToMany,
    OneToOne,
};
use InvalidArgumentException;

/**
 * Foundation of an organ.
 */
#[Entity]
class Foundation extends SubDecision
{
    public const ORGAN_TYPE_COMMITTEE = 'committee';
    public const ORGAN_TYPE_AVC = 'avc';
    public const ORGAN_TYPE_FRATERNITY = 'fraternity';
    public const ORGAN_TYPE_AVW = 'avw';
    public const ORGAN_TYPE_KKK = 'kkk';
    public const ORGAN_TYPE_RVA = 'rva';

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
    #[Column(type: "string")]
    protected string $organType;

    /**
     * References from other subdecisions to this organ.
     */
    #[OneToMany(
        targetEntity: "Decision\Model\SubDecision\FoundationReference",
        mappedBy: "foundation",
    )]
    protected ArrayCollection $references;

    /**
     * Organ entry for this organ.
     */
    #[OneToOne(
        targetEntity: "Decision\Model\Organ",
        mappedBy: "foundation",
    )]
    protected Organ $organ;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

    /**
     * Get available organ types.
     *
     * @return array
     */
    public static function getOrganTypes()
    {
        return [
            self::ORGAN_TYPE_COMMITTEE,
            self::ORGAN_TYPE_AVC,
            self::ORGAN_TYPE_FRATERNITY,
            self::ORGAN_TYPE_AVW,
            self::ORGAN_TYPE_KKK,
            self::ORGAN_TYPE_RVA,
        ];
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
     * @return string
     */
    public function getOrganType(): string
    {
        return $this->organType;
    }

    /**
     * Set the type.
     *
     * @param string $organType
     *
     * @throws InvalidArgumentException if the type is wrong
     */
    public function setOrganType(string $organType): void
    {
        if (!in_array($organType, self::getOrganTypes())) {
            throw new InvalidArgumentException('Given type does not exist.');
        }
        $this->organType = $organType;
    }

    /**
     * Get the references.
     *
     * @return ArrayCollection of references
     */
    public function getReferences(): ArrayCollection
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
