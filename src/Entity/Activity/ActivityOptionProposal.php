<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ as OrganModel;
use App\Repository\Activity\ActivityOptionProposalRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Activity calendar activity option proposal model.
 */
#[Entity(repositoryClass: ActivityOptionProposalRepository::class)]
class ActivityOptionProposal
{
    use IdentifiableTrait;

    /**
     * Name for the activity option proposal.
     */
    #[Column(type: Types::STRING)]
    private string $name;

    /**
     * Description for the activity option proposal.
     */
    #[Column(type: Types::STRING)]
    private string $description;

    /**
     * Who created this activity option.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $creator;

    /**
     * The date and time the activity option was created.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $creationTime;

    /**
     * Who created this activity proposal.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?OrganModel $organ = null;

    /**
     * Who created this activity proposal, if not an organ.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $organAlt = null;

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

    public function getOrgan(): ?OrganModel
    {
        return $this->organ;
    }

    public function setOrgan(?OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * Returns in order of presense:
     * 1. The abbreviation of the related organ
     * 2. The alternative for an organ, other organising parties
     * 3. The full name of the member who created the proposal.
     */
    public function getCreatorAlt(): string
    {
        if (null !== ($organ = $this->getOrgan())) {
            return $organ->getAbbr();
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
