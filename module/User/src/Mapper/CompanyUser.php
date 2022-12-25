<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\CompanyUser as CompanyUserModel;

class CompanyUser extends BaseMapper
{
    /**
     * Find a company by its login.
     */
    public function findByLogin(string $login): ?CompanyUserModel
    {
        // create query for company
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u, c')
            ->from($this->getRepositoryName(), 'u')
            ->join('u.company', 'c')
            ->where('LOWER(c.representativeEmail) = ?1');

        // set the parameters
        $qb->setParameter(1, strtolower($login));
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyUserModel::class;
    }
}
