<?php

namespace Company\Model\Proposals;

use Application\Model\Traits\IdentifiableTrait;
use Company\Model\Job as JobModel;
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    ManyToOne,
    OneToOne,
};

#[Entity]
class JobUpdate
{
    use IdentifiableTrait;

    /**
     * The current company, to which an update is proposed.
     */
    #[ManyToOne(
        targetEntity: JobModel::class,
        inversedBy: "updateProposals",
    )]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected JobModel $current;

    /**
     * The proposed update of the company.
     */
    #[OneToOne(targetEntity: JobModel::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected JobModel $proposal;

    /**
     * @return JobModel
     */
    public function getCurrent(): JobModel
    {
        return $this->current;
    }

    /**
     * @param JobModel $current
     */
    public function setCurrent(JobModel $current): void
    {
        $this->current = $current;
    }

    /**
     * @return JobModel
     */
    public function getProposal(): JobModel
    {
        return $this->proposal;
    }

    /**
     * @param JobModel $proposal
     */
    public function setProposal(JobModel $proposal): void
    {
        $this->proposal = $proposal;
    }
}
