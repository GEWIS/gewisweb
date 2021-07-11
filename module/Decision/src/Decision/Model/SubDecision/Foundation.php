<?php

namespace Decision\Model\SubDecision;

use Decision\Model\Organ;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

use Decision\Model\SubDecision;
use InvalidArgumentException;

/**
 * Foundation of an organ.
 *
 * @ORM\Entity
 */
class Foundation extends SubDecision
{
    const ORGAN_TYPE_COMMITTEE = 'committee';
    const ORGAN_TYPE_AVC = 'avc';
    const ORGAN_TYPE_FRATERNITY = 'fraternity';
    const ORGAN_TYPE_AVW = 'avw';
    const ORGAN_TYPE_KKK = 'kkk';
    const ORGAN_TYPE_RVA = 'rva';

    /**
     * Abbreviation (only for when organs are created)
     *
     * @ORM\Column(type="string")
     */
    protected $abbr;

    /**
     * Name (only for when organs are created)
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Type of the organ.
     *
     * @ORM\Column(type="string")
     */
    protected $organType;

    /**
     * References from other subdecisions to this organ.
     *
     * @ORM\OneToMany(targetEntity="FoundationReference",mappedBy="foundation")
     */
    protected $references;

    /**
     * Organ entry for this organ.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\Organ",mappedBy="foundation")
     */
    protected $organ;

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
            self::ORGAN_TYPE_RVA
        ];
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
     * Get the type.
     *
     * @return string
     */
    public function getOrganType()
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
    public function setOrganType($organType)
    {
        if (!in_array($organType, self::getOrganTypes())) {
            throw new InvalidArgumentException("Given type does not exist.");
        }
        $this->organType = $organType;
    }

    /**
     * Get the references.
     *
     * @return array of references
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * Get the referenced organ.
     *
     * @return Organ
     */
    public function getOrgan()
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
    public function toArray()
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
            'organtype' => $this->getOrganType()
        ];
    }
}
