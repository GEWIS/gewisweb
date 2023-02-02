<?php

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\{
    Member as MemberModel,
    Organ as OrganModel,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
};
use User\Permissions\Resource\OrganResourceInterface;

/**
 * Activity calendar activity option proposal model.
 */
#[Entity]
class ActivityOptionProposal implements OrganResourceInterface
{
    use IdentifiableTrait;

    /**
     * Name for the activity option proposal.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Description for the activity option proposal.
     */
    #[Column(type: "string")]
    protected string $description;

    /**
     * Who created this activity option.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected MemberModel $creator;

    /**
     * The date and time the activity option was created.
     */
    #[Column(type: "datetime")]
    protected DateTime $creationTime;

    /**
     * Who created this activity proposal.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: true,
    )]
    protected ?OrganModel $organ = null;

    /**
     * Who created this activity proposal, if not an organ.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $organAlt = null;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return DateTime
     */
    public function getCreationTime(): DateTime
    {
        return $this->creationTime;
    }

    /**
     * @param DateTime $creationTime
     */
    public function setCreationTime(DateTime $creationTime): void
    {
        $this->creationTime = $creationTime;
    }

    /**
     * Get the organ of this resource.
     *
     * @return OrganModel|null
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getOrgan();
    }

    /**
     * @return OrganModel|null
     */
    public function getOrgan(): ?OrganModel
    {
        return $this->organ;
    }

    /**
     * @param OrganModel|null $organ
     */
    public function setOrgan(?OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * @return OrganModel|string
     */
    public function getOrganOrAlt(): OrganModel|string
    {
        if ($this->organ) {
            return $this->organ;
        }

        return $this->organAlt;
    }

    /**
     * Returns the string identifier of the Resource.
     *
     * @return int|string
     */
    public function getResourceId(): int|string
    {
        return $this->getId();
    }

    /**
     * Returns in order of presense:
     * 1. The abbreviation of the related organ
     * 2. The alternative for an organ, other organising parties
     * 3. The full name of the member who created the proposal.
     *
     * @return string
     */
    public function getCreatorAlt(): string
    {
        if (!is_null($this->getOrgan())) {
            return $this->getOrgan()->getAbbr();
        }

        if (!is_null($this->getOrganAlt())) {
            return $this->getOrganAlt();
        }

        return $this->getCreator()->getFullName();
    }

    /**
     * @return string|null
     */
    public function getOrganAlt(): ?string
    {
        return $this->organAlt;
    }

    /**
     * @param string|null $organAlt
     */
    public function setOrganAlt(?string $organAlt): void
    {
        $this->organAlt = $organAlt;
    }

    /**
     * @return MemberModel
     */
    public function getCreator(): MemberModel
    {
        return $this->creator;
    }

    /**
     * @param MemberModel $creator
     */
    public function setCreator(MemberModel $creator): void
    {
        $this->creator = $creator;
    }
}
