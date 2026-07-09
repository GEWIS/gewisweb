<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Company::class,
        );
    }

    /**
     * Find all public companies, these are companies that have a live (approved) revision, are published and have at
     * least one non-expired package.
     *
     * @return Company[]
     */
    public function findAllPublic(): array
    {
        $rsmBuilder = new ResultSetMappingBuilder($this->getEntityManager());
        $rsmBuilder->addRootEntityFromClassMetadata(
            $this->getEntityName(),
            'c',
        );

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
            AND `t1`.`liveRevision_id` IS NOT NULL
            AND `CompanyPackages`.`totalPackages` > `CompanyPackages`.`expiredHiddenOrNotStartedPackages`
            ORDER BY `t1`.`name` ASC
            QUERY;

        return $this->getEntityManager()->createNativeQuery(
            $sql,
            $rsmBuilder,
        )->getResult();
    }

    /**
     * Return the company with the given slug.
     *
     * @param string $slugName the slugname to find
     */
    public function findCompanyBySlugName(string $slugName): ?Company
    {
        return $this->findOneBy(['slugName' => $slugName]);
    }

    /**
     * Return a company by a given representative's email address.
     */
    public function findCompanyByRepresentativeEmail(string $email): ?Company
    {
        return $this->findOneBy(['representativeEmail' => $email]);
    }
}
