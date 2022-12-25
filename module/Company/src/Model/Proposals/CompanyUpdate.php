<?php

namespace Company\Model\Proposals;

use Application\Model\Traits\IdentifiableTrait;
use Company\Model\Company as CompanyModel;
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    ManyToOne,
    OneToOne,
};

#[Entity]
class CompanyUpdate
{
    use IdentifiableTrait;

    /**
     * The current company, to which an update is proposed.
     */
    #[ManyToOne(
        targetEntity: CompanyModel::class,
        inversedBy: "updateProposals",
    )]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyModel $current;

    /**
     * The proposed update of the company.
     */
    #[OneToOne(targetEntity: CompanyModel::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyModel $proposal;

    /**
     * @return CompanyModel
     */
    public function getCurrent(): CompanyModel
    {
        return $this->current;
    }

    /**
     * @param CompanyModel $current
     */
    public function setCurrent(CompanyModel $current): void
    {
        $this->current = $current;
    }

    /**
     * @return CompanyModel
     */
    public function getProposal(): CompanyModel
    {
        return $this->proposal;
    }

    /**
     * @param CompanyModel $proposal
     */
    public function setProposal(CompanyModel $proposal): void
    {
        $this->proposal = $proposal;
    }
}
