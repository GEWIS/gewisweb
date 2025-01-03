<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Application\Model\Enums\ApprovableStatus;
use Decision\Model\OrganInformation as OrganInformationModel;
use Decision\Model\Proposals\OrganInformationUpdate as OrganInformationUpdateModel;

/**
 * @template-extends BaseMapper<OrganInformationModel>
 */
class OrganInformation extends BaseMapper
{
    /**
     * @return OrganInformationModel[]
     */
    public function findProposals(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('oi');
        $qb->where('(oi.approved = :approved AND oi.isUpdate = :isUpdate)');

        $qbu = $this->getEntityManager()->createQueryBuilder();
        $qbu->select('IDENTITY(u.original)')->distinct()
            ->from(OrganInformationUpdateModel::class, 'u')
            ->innerJoin('u.proposal', 'p')
            ->where('p.approved = :approved')
            ->orderBy('u.id', 'DESC');

        $qb->orWhere($qb->expr()->in('oi.id', $qbu->getDQL()))
            ->orderBy('oi.id', 'DESC');

        $qb->setParameter('approved', ApprovableStatus::Unapproved)
            ->setParameter('isUpdate', false);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return OrganInformationModel::class;
    }
}
