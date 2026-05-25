<?php

declare(strict_types=1);

namespace App\Entity\Career\Proposals;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Career\Job as JobModel;
use App\Repository\Career\Proposals\JobUpdateRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity(repositoryClass: JobUpdateRepository::class)]
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
    private JobModel $original;

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
    private JobModel $proposal;

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
