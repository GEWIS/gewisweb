<?php

declare(strict_types=1);

namespace Decision\Model\Proposals;

use Application\Model\Traits\IdentifiableTrait;
use Decision\Model\OrganInformation as OrganInformationModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class OrganInformationUpdate
{
    use IdentifiableTrait;

    /**
     * The current {@link OrganInformationModel}, for which an update is proposed.
     */
    #[ManyToOne(
        targetEntity: OrganInformationModel::class,
        inversedBy: 'updateProposals',
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected OrganInformationModel $original;

    /**
     * The proposed update of the {@link OrganInformationModel}.
     */
    #[OneToOne(
        targetEntity: OrganInformationModel::class,
        cascade: ['remove'],
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected OrganInformationModel $proposal;

    /**
     * Get the original {@link OrganInformationModel}.
     */
    public function getOriginal(): OrganInformationModel
    {
        return $this->original;
    }

    /**
     * Set the original {@link OrganInformationModel}.
     */
    public function setOriginal(OrganInformationModel $original): void
    {
        $this->original = $original;
    }

    /**
     * Get the proposed update of {@link OrganInformationUpdate::$original}.
     */
    public function getProposal(): OrganInformationModel
    {
        return $this->proposal;
    }

    /**
     * Set the proposed update for {@link OrganInformationUpdate::$original}.
     */
    public function setProposal(OrganInformationModel $proposal): void
    {
        $this->proposal = $proposal;
    }
}
