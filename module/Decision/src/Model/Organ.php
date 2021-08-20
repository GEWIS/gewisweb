<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Foundation;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    InverseJoinColumn,
    JoinColumn,
    JoinTable,
    ManyToMany,
    OneToMany,
    OneToOne,
};

/**
 * Organ entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class Organ
{
    public const ORGAN_TYPE_COMMITTEE = 'committee';
    public const ORGAN_TYPE_AVC = 'avc';
    public const ORGAN_TYPE_FRATERNITY = 'fraternity';
    public const ORGAN_TYPE_AVW = 'avw';
    public const ORGAN_TYPE_KKK = 'kkk';
    public const ORGAN_TYPE_RVA = 'rva';

    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

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
    protected string $type;

    /**
     * Reference to foundation of organ.
     */
    #[OneToOne(
        targetEntity: Foundation::class,
        inversedBy: "organ",
    )]
    #[JoinColumn(
        name: "r_meeting_type",
        referencedColumnName: "meeting_type",
    )]
    #[JoinColumn(
        name: "r_meeting_number",
        referencedColumnName: "meeting_number",
    )]
    #[JoinColumn(
        name: "r_decision_point",
        referencedColumnName: "decision_point",
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "decision_number",
    )]
    #[JoinColumn(
        name: "r_number",
        referencedColumnName: "number",
    )]
    protected Foundation $foundation;

    /**
     * Foundation date.
     */
    #[Column(type: "date")]
    protected DateTime $foundationDate;

    /**
     * Abrogation date.
     */
    #[Column(
        type: "date",
        nullable: true,
    )]
    protected ?DateTime $abrogationDate = null;

    /**
     * Reference to members.
     */
    #[OneToMany(
        targetEntity: OrganMember::class,
        mappedBy: "organ",
    )]
    protected Collection $members;

    /**
     * Reference to subdecisions.
     */
    #[ManyToMany(targetEntity: SubDecision::class)]
    #[JoinTable(name: "organs_subdecisions")]
    #[JoinColumn(
        name: "organ_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: "meeting_type",
        referencedColumnName: "meeting_type",
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: "meeting_number",
        referencedColumnName: "meeting_number",
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: "decision_point",
        referencedColumnName: "decision_point",
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: "decision_number",
        referencedColumnName: "decision_number",
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: "subdecision_number",
        referencedColumnName: "number",
        nullable: false,
    )]
    protected Collection $subdecisions;

    /**
     * All organInformation for this organ.
     */
    #[OneToMany(
        targetEntity: OrganInformation::class,
        mappedBy: "organ",
        cascade: ["persist", "remove"],
    )]
    protected Collection $organInformation;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->subdecisions = new ArrayCollection();
        $this->organInformation = new ArrayCollection();
    }

    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID.
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
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
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the foundation.
     *
     * @return Foundation
     */
    public function getFoundation(): Foundation
    {
        return $this->foundation;
    }

    /**
     * Set the foundation.
     *
     * @param Foundation $foundation
     */
    public function setFoundation(Foundation $foundation): void
    {
        $this->foundation = $foundation;
    }

    /**
     * Get the foundation date.
     *
     * @return DateTime
     */
    public function getFoundationDate(): DateTime
    {
        return $this->foundationDate;
    }

    /**
     * Set the foundation date.
     *
     * @param DateTime $foundationDate
     */
    public function setFoundationDate(DateTime $foundationDate): void
    {
        $this->foundationDate = $foundationDate;
    }

    /**
     * Get the abrogation date.
     *
     * @return DateTime|null
     */
    public function getAbrogationDate(): ?DateTime
    {
        return $this->abrogationDate;
    }

    /**
     * Set the abrogation date.
     *
     * @param DateTime|null $abrogationDate
     */
    public function setAbrogationDate(?DateTime $abrogationDate): void
    {
        $this->abrogationDate = $abrogationDate;
    }

    /**
     * Get the members.
     *
     * @return Collection of OrganMember
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param array $subdecisions
     */
    public function addSubdecisions(array $subdecisions): void
    {
        foreach ($subdecisions as $subdecision) {
            $this->addSubdecision($subdecision);
        }
    }

    /**
     * Add a subdecision.
     *
     * @param SubDecision $subdecision
     */
    public function addSubdecision(SubDecision $subdecision): void
    {
        if (!$this->subdecisions->contains($subdecision)) {
            $this->subdecisions[] = $subdecision;
        }
    }

    /**
     * Get all subdecisions.of this organ.
     *
     * @return Collection
     */
    public function getSubdecisions(): Collection
    {
        return $this->subdecisions;
    }

    /**
     * Get all subdecisions of this organ ordered by upload order.
     *
     * @return array subdecisions[0]->getDate < subdecision[1]->getDate
     */
    public function getOrderedSubdecisions()
    {
        $array = $this->subdecisions->toArray();
        usort($array, function ($dA, $dB) {
            return ($dA->getDecision()->getMeeting()->getDate() > $dB->getDecision()->getMeeting()->getDate() ? -1 : 1);
        });

        return $array;
    }

    /**
     * Returns all organ information.
     *
     * @return Collection
     */
    public function getOrganInformation(): Collection
    {
        return $this->organInformation;
    }

    /**
     * Returns the approved information for an organ.
     *
     * @return OrganInformation|null
     */
    public function getApprovedOrganInformation(): ?OrganInformation
    {
        foreach ($this->organInformation as $information) {
            if (!is_null($information->getApprover())) {
                return $information;
            }
        }

        return null;
    }
}
