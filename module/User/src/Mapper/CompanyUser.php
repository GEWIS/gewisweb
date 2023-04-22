<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\CompanyUser as CompanyUserModel;

/**
 * @template-extends BaseMapper<CompanyUserModel>
 */
class CompanyUser extends BaseMapper
{
    /**
     * Find a company by its login.
     */
    public function findByLogin(string $login): ?CompanyUserModel
    {
        // create query for company
        $qb = $this->getRepository()->createQueryBuilder('u');
        $qb->join('u.company', 'c')
            ->where('LOWER(c.representativeEmail) = :email')
            ->setParameter(':email', strtolower($login));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyUserModel::class;
    }
}
