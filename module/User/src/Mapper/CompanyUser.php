<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\{
    NewCompanyUser as NewCompanyUserModel,
    CompanyUser as CompanyUserModel,
};

class CompanyUser extends BaseMapper
{
    /**
     * Find a company by its login.
     *
     * @param string $login
     *
     * @return CompanyUserModel|null
     */
    public function findByLogin(string $login): ?CompanyUserModel
    {
        // create query for company
        $qb = $this->em->createQueryBuilder();
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
     * Finish company account creation.
     *
     * This will both destroy the NewCompanyUser and create the given company.
     *
     * @param CompanyUserModel $companyUser CompanyUser to create
     * @param NewCompanyUserModel $newCompanyUser NewCompanyUser to destroy
     */
    public function createUser(
        CompanyUserModel $companyUser,
        NewCompanyUserModel $newCompanyUser,
    ): void {
        $this->em->persist($companyUser);
        $this->em->remove($newCompanyUser);
        $this->em->flush();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyUserModel::class;
    }
}
