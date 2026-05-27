<?php

declare(strict_types=1);

namespace App\Entity\Career\Proposals;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Career\Company as CompanyModel;
use App\Repository\Career\Proposals\CompanyUpdateRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity(repositoryClass: CompanyUpdateRepository::class)]
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
    private CompanyModel $original;

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
    private CompanyModel $proposal;

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
