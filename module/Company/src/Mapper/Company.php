<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * Mappers for companies.
 *
 * NOTE: Companies will be modified externally by a script. Modifications will be
 * overwritten.
 */
class Company extends BaseMapper
{
    /**
     * Find all public companies, these are companies that are published and have at least one non-expired package.
     *
     * @return array
     */
    public function findAllPublic(): array
    {
        $rsmBuilder = new ResultSetMappingBuilder($this->getEntityManager());
        $rsmBuilder->addRootEntityFromClassMetadata($this->getRepositoryName(), 'c');

        $select = $rsmBuilder->generateSelectClause(['c' => 't1']);

        $sql = <<<QUERY
            SELECT {$select} FROM `Company` AS `t1`
            LEFT JOIN (
                SELECT `company_id`,
                    COUNT(`company_id`) AS `totalPackages`,
                    SUM(
                        CASE WHEN `expires` <= CURRENT_TIMESTAMP
                                OR `published` = 0
                                OR `starts` > CURRENT_TIMESTAMP
                            THEN 1
                            ELSE 0
                        END
                    ) AS `expiredHiddenOrNotStartedPackages`
                FROM `CompanyPackage`
                GROUP BY `company_id`
            ) `CompanyPackages` ON `CompanyPackages`.`company_id` = `t1`.`id`
            WHERE `t1`.`published` = 1
            AND `CompanyPackages`.`totalPackages` > `CompanyPackages`.`expiredHiddenOrNotStartedPackages`
            ORDER BY `t1`.`name` ASC
            QUERY;

        return $this->getEntityManager()->createNativeQuery($sql, $rsmBuilder)->getResult();
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
     * Return a company by a given representative's e-mail address.
     *
     * @param string $email
     *
     * @return CompanyModel|null
     */
    public function findCompanyByRepresentativeEmail(string $email): ?CompanyModel
    {
        return $this->getRepository()->findOneBy(
            [
                'representativeEmail' => $email,
            ],
        );
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyModel::class;
    }
}
