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
     * The current job, to which an update is proposed.
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
     * The proposed update of the company.
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
     * Get the original `Job`.
     */
    public function getOriginal(): JobModel
    {
        return $this->original;
    }

    /**
     * Set the original `Job`.
     */
    public function setOriginal(JobModel $original): void
    {
        $this->original = $original;
    }

    /**
     * Get the proposed update of `$original`.
     */
    public function getProposal(): JobModel
    {
        return $this->proposal;
    }

    /**
     * Set the proposed update for `$original`.
     */
    public function setProposal(JobModel $proposal): void
    {
        $this->proposal = $proposal;
    }
}
