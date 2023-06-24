<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\SubDecision\Foundation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

use function usort;

/**
 * Organ entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class Organ
{
    use IdentifiableTrait;

    /**
     * Abbreviation (only for when organs are created).
     */
    #[Column(type: 'string')]
    protected string $abbr;

    /**
     * Name (only for when organs are created).
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
    protected OrganTypes $type;

    /**
     * Reference to foundation of organ.
     */
    #[OneToOne(
        inversedBy: 'organ',
        targetEntity: Foundation::class,
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_number',
        referencedColumnName: 'number',
    )]
    protected Foundation $foundation;

    /**
     * Foundation date.
     */
    #[Column(type: 'date')]
    protected DateTime $foundationDate;

    /**
     * Abrogation date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $abrogationDate = null;

    /**
     * Reference to members.
     *
     * @var Collection<array-key, OrganMember>
     */
    #[OneToMany(
        mappedBy: 'organ',
        targetEntity: OrganMember::class,
    )]
    protected Collection $members;

    /**
     * Reference to subdecisions.
     *
     * @var Collection<array-key, SubDecision>
     */
    #[ManyToMany(targetEntity: SubDecision::class)]
    #[JoinTable(name: 'organs_subdecisions')]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'meeting_type',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'meeting_number',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'decision_point',
        referencedColumnName: 'decision_point',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'decision_number',
        referencedColumnName: 'decision_number',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'subdecision_number',
        referencedColumnName: 'number',
        nullable: false,
    )]
    protected Collection $subdecisions;

    /**
     * All organInformation for this organ.
     *
     * @var Collection<array-key, OrganInformation>
     */
    #[OneToMany(
        mappedBy: 'organ',
        targetEntity: OrganInformation::class,
        cascade: ['persist', 'remove'],
    )]
    protected Collection $organInformation;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->subdecisions = new ArrayCollection();
        $this->organInformation = new ArrayCollection();
    }

    /**
     * Get the abbreviation.
     */
    public function getAbbr(): string
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     */
    public function setAbbr(string $abbr): void
    {
        $this->abbr = $abbr;
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
     * Get the type.
     */
    public function getType(): OrganTypes
    {
        return $this->type;
    }

    /**
     * Set the type.
     */
    public function setType(OrganTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the foundation.
     */
    public function getFoundation(): Foundation
    {
        return $this->foundation;
    }

    /**
     * Set the foundation.
     */
    public function setFoundation(Foundation $foundation): void
    {
        $this->foundation = $foundation;
    }

    /**
     * Get the foundation date.
     */
    public function getFoundationDate(): DateTime
    {
        return $this->foundationDate;
    }

    /**
     * Set the foundation date.
     */
    public function setFoundationDate(DateTime $foundationDate): void
    {
        $this->foundationDate = $foundationDate;
    }

    /**
     * Get the abrogation date.
     */
    public function getAbrogationDate(): ?DateTime
    {
        return $this->abrogationDate;
    }

    /**
     * Set the abrogation date.
     */
    public function setAbrogationDate(?DateTime $abrogationDate): void
    {
        $this->abrogationDate = $abrogationDate;
    }

    /**
     * Get the members.
     *
     * @return Collection<array-key, OrganMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param SubDecision[] $subdecisions
     */
    public function addSubdecisions(array $subdecisions): void
    {
        foreach ($subdecisions as $subdecision) {
            $this->addSubdecision($subdecision);
        }
    }

    /**
     * Add a subdecision.
     */
    public function addSubdecision(SubDecision $subdecision): void
    {
        if ($this->subdecisions->contains($subdecision)) {
            return;
        }

        $this->subdecisions[] = $subdecision;
    }

    /**
     * Get all subdecisions of this organ.
     *
     * @return Collection<array-key, SubDecision>
     */
    public function getSubdecisions(): Collection
    {
        return $this->subdecisions;
    }

    /**
     * Get all subdecisions of this organ ordered by upload order.
     *
     * @return SubDecision[] subdecisions[0]->getDate < subdecision[1]->getDate
     */
    public function getOrderedSubdecisions(): array
    {
        $array = $this->subdecisions->toArray();
        usort($array, static function ($dA, $dB) {
            return $dA->getDecision()->getMeeting()->getDate() > $dB->getDecision()->getMeeting()->getDate() ? -1 : 1;
        });

        return $array;
    }

    /**
     * Returns all organ information.
     *
     * @return Collection<array-key, OrganInformation>
     */
    public function getOrganInformation(): Collection
    {
        return $this->organInformation;
    }

    /**
     * Returns the approved information for an organ.
     */
    public function getApprovedOrganInformation(): ?OrganInformation
    {
        foreach ($this->organInformation as $information) {
            if (null !== $information->getApprover()) {
                return $information;
            }
        }

        return null;
    }
}
