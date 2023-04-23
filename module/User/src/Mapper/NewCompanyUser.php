<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;
use User\Model\NewCompanyUser as NewCompanyUserModel;

/**
 * @template-extends BaseMapper<NewCompanyUserModel>
 */
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
     */
    public function deleteByCompany(CompanyModel $company): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getRepositoryName(), 'u')
            ->where('u.company = :company')
            ->setParameter('company', $company);
        $qb->getQuery()->execute();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return NewCompanyUserModel::class;
    }
}
