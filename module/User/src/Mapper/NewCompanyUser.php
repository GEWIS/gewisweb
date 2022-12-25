<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;
use User\Model\NewCompanyUser as NewCompanyUserModel;

class NewCompanyUser extends BaseMapper
{
    /**
     * Get the new company user by code.
     *
     * @param string $code
     *
     * @return NewCompanyUserModel|null
     */
    public function getByCode(string $code): ?NewCompanyUserModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u, c')
            ->from($this->getRepositoryName(), 'u')
            ->join('u.company', 'c')
            ->where('u.code = :code');
        $qb->setParameter('code', $code);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Delete the existing activation code for a company.
     *
     * @param CompanyModel $company
     *
     * @return int
     */
    public function deleteByCompany(CompanyModel $company): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getRepositoryName(), 'u');
        $qb->where('u.company = :company');
        $qb->setParameter('company', $company);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return NewCompanyUserModel::class;
    }
}
