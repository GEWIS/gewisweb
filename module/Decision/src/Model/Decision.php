<?php

declare(strict_types=1);

namespace Decision\Model;

use Decision\Model\Enums\MeetingTypes;
use Decision\Model\SubDecision\Annulment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * Decision model.
 */
#[Entity]
class Decision
{
    /**
     * Meeting.
     */
    #[ManyToOne(
        targetEntity: Meeting::class,
        inversedBy: 'decisions',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
        nullable: false,
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
        nullable: false,
    )]
    protected Meeting $meeting;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     */
    #[Id]
    #[Column(
        type: 'string',
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $meeting_type;

    /**
     * Meeting number.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $meeting_number;

    /**
     * Point in the meeting in which the decision was made.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $point;

    /**
     * Decision number.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $number;

    /**
     * Content in Dutch.
     *
     * Generated from subdecisions.
     */
    #[Column(type: 'text')]
    protected string $contentNL;

    /**
     * Content in English.
     *
     * Generated from subdecisions.
     */
    #[Column(type: 'text')]
    protected string $contentEN;

    /**
     * Subdecisions.
     *
     * @var Collection<array-key, SubDecision>
     */
    #[OneToMany(
        targetEntity: SubDecision::class,
        mappedBy: 'decision',
        cascade: ['persist', 'remove'],
    )]
    #[OrderBy(value: ['sequence' => 'ASC'])]
    protected Collection $subdecisions;

    /**
     * Annulled by.
     */
    #[OneToOne(
        targetEntity: Annulment::class,
        mappedBy: 'target',
    )]
    protected ?Annulment $annulledBy = null;

    /**
     * Set the meeting.
     */
    public function setMeeting(Meeting $meeting): void
    {
        $this->subdecisions = new ArrayCollection();

        $meeting->addDecision($this);
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
        $this->meeting = $meeting;
    }

    /**
     * Get the meeting type.
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     */
    public function getMeetingNumber(): int
    {
        return $this->meeting_number;
    }

    /**
     * Get the meeting.
     */
    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the point number.
     */
    public function setPoint(int $point): void
    {
        $this->point = $point;
    }

    /**
     * Get the point number.
     */
    public function getPoint(): int
    {
        return $this->point;
    }

    /**
     * Set the decision number.
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * Get the decision number.
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Get decision content in Dutch.
     */
    public function getContentNL(): string
    {
        return $this->contentNL;
    }

    /**
     * Set decision content in Dutch.
     */
    public function setContentNL(string $content): void
    {
        $this->contentNL = $content;
    }

    /**
     * Get decision content in English.
     */
    public function getContentEN(): string
    {
        return $this->contentEN;
    }

    /**
     * Set decision content in English.
     */
    public function setContentEN(string $content): void
    {
        $this->contentEN = $content;
    }

    /**
     * Get the subdecisions.
     *
     * @return Collection<array-key, SubDecision>
     */
    public function getSubdecisions(): Collection
    {
        return $this->subdecisions;
    }

    /**
     * Add a subdecision.
     */
    public function addSubdecision(SubDecision $subdecision): void
    {
        $this->subdecisions[] = $subdecision;
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
     * Get the subdecision by which this decision is annulled.
     *
     * Or null, if it wasn't annulled.
     */
    public function getAnnulledBy(): ?Annulment
    {
        return $this->annulledBy;
    }

    /**
     * Check if this decision is annulled by another decision.
     */
    public function isAnnulled(): bool
    {
        return null !== $this->annulledBy;
    }
}
