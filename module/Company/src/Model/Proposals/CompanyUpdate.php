<?php

declare(strict_types=1);

namespace Company\Model\Proposals;

use Application\Model\Traits\IdentifiableTrait;
use Company\Model\Company as CompanyModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class CompanyUpdate
{
    use IdentifiableTrait;

    /**
     * The current company, to which an update is proposed.
     */
    #[ManyToOne(
        targetEntity: CompanyModel::class,
        inversedBy: 'updateProposals',
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyModel $original;

    /**
     * The proposed update of the company.
     */
    #[OneToOne(
        targetEntity: CompanyModel::class,
        cascade: ['remove'],
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyModel $proposal;

    public function getOriginal(): CompanyModel
    {
        return $this->original;
    }

    public function setOriginal(CompanyModel $original): void
    {
        $this->original = $original;
    }

    public function getProposal(): CompanyModel
    {
        return $this->proposal;
    }

    public function setProposal(CompanyModel $proposal): void
    {
        $this->proposal = $proposal;
    }
}
