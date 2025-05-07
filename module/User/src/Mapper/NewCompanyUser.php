<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;
use Override;
use User\Model\NewCompanyUser as NewCompanyUserModel;

/**
 * @template-extends BaseMapper<NewCompanyUserModel>
 */
class NewCompanyUser extends BaseMapper
{
    /**
     * Get the new company user by code.
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
     */
    public function deleteByCompany(CompanyModel $company): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getRepositoryName(), 'u')
            ->where('u.company = :company')
            ->setParameter('company', $company);
        $qb->getQuery()->execute();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return NewCompanyUserModel::class;
    }
}
