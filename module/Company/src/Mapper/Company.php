<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;

/**
 * Mappers for companies.
 *
 * NOTE: Companies will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Company extends BaseMapper
{
    /**
     * Find all public companies with a certain locale.
     *
     * @return array
     */
    public function findAllPublic(): array
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.published = 1')
            ->orderBy('c.name', 'ASC');

        return array_filter(
            $qb->getQuery()->getResult(),
            function ($company) {
                return $company->getNumberOfPackages() > $company->getNumberOfExpiredPackages();
            }
        );
    }

    /**
     * Find a specific company by its id.
     *
     * @param int $id The id of the company
     *
     * @return CompanyModel|null
     */
    public function findById(int $id): ?CompanyModel
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Find all companies.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Return the company with the given slug.
     *
     * @param string $slugName the slugname to find
     *
     * @return CompanyModel|null
     */
    public function findCompanyBySlugName(string $slugName): ?CompanyModel
    {
        $result = $this->getRepository()->findBy(['slugName' => $slugName]);

        return empty($result) ? null : $result[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyModel::class;
    }
}
