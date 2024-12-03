<?php

declare(strict_types=1);

namespace Company\Model\Proposals;

use Application\Model\Traits\IdentifiableTrait;
use Company\Model\Job as JobModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class JobUpdate
{
    use IdentifiableTrait;

    /**
     * The current {@link JobModel}, for which an update is proposed.
     */
    #[ManyToOne(
        targetEntity: JobModel::class,
        inversedBy: 'updateProposals',
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected JobModel $original;

    /**
     * The proposed update of the {@link JobModel}.
     */
    #[OneToOne(
        targetEntity: JobModel::class,
        cascade: ['remove'],
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected JobModel $proposal;

    /**
     * Get the original {@link JobModel}.
     */
    public function getOriginal(): JobModel
    {
        return $this->original;
    }

    /**
     * Set the original {@link JobModel}.
     */
    public function setOriginal(JobModel $original): void
    {
        $this->original = $original;
    }

    /**
     * Get the proposed update of {@link JobUpdate::$original}.
     */
    public function getProposal(): JobModel
    {
        return $this->proposal;
    }

    /**
     * Set the proposed update for {@link JobUpdate::$original}.
     */
    public function setProposal(JobModel $proposal): void
    {
        $this->proposal = $proposal;
    }
}
