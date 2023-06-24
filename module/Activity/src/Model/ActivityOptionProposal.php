<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
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
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Description for the activity option proposal.
     */
    #[Column(type: 'string')]
    protected string $description;

    /**
     * Who created this activity option.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $creator;

    /**
     * The date and time the activity option was created.
     */
    #[Column(type: 'datetime')]
    protected DateTime $creationTime;

    /**
     * Who created this activity proposal.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    protected ?OrganModel $organ = null;

    /**
     * Who created this activity proposal, if not an organ.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $organAlt = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCreationTime(): DateTime
    {
        return $this->creationTime;
    }

    public function setCreationTime(DateTime $creationTime): void
    {
        $this->creationTime = $creationTime;
    }

    /**
     * Get the organ of this resource.
     */
    public function getResourceOrgan(): ?OrganModel
    {
        return $this->getOrgan();
    }

    public function getOrgan(): ?OrganModel
    {
        return $this->organ;
    }

    public function setOrgan(?OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * Returns the string identifier of the Resource.
     */
    public function getResourceId(): string
    {
        return (string) $this->getId();
    }

    /**
     * Returns in order of presense:
     * 1. The abbreviation of the related organ
     * 2. The alternative for an organ, other organising parties
     * 3. The full name of the member who created the proposal.
     */
    public function getCreatorAlt(): string
    {
        if (null !== $this->getOrgan()) {
            return $this->getOrgan()->getAbbr();
        }

        if (null !== ($organAlt = $this->getOrganAlt())) {
            return $organAlt;
        }

        return $this->getCreator()->getFullName();
    }

    public function getOrganAlt(): ?string
    {
        return $this->organAlt;
    }

    public function setOrganAlt(?string $organAlt): void
    {
        $this->organAlt = $organAlt;
    }

    public function getCreator(): MemberModel
    {
        return $this->creator;
    }

    public function setCreator(MemberModel $creator): void
    {
        $this->creator = $creator;
    }
}
