<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyJobPackage;
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

        $companies = $this->getEntityManager()->createNativeQuery(
            $sql,
            $rsmBuilder,
        )->getResult();

        if ([] === $companies) {
            return [];
        }

        $this->warmOverviewAssociations($companies);

        return $companies;
    }

    /**
     * Eagerly loads everything the company overview renders onto the given (already managed) companies, so the page
     * does not fan out into a lazy load per association.
     *
     * The overview reads each company's live revision and its localised texts (slogan, description, website), plus the
     * active vacancies grouped per package. Since every {@see CareerLocalisedText} lives in its own row, lazy loading
     * them is the dominant source of the N+1 explosion. Two fetch-joining queries warm the identity map instead: one
     * for the companies (a single to-many join on the packages) and one for the vacancies (whose collection lives on
     * the {@see CompanyJobPackage} subclass and so cannot be joined through the base package in the same query). The
     * results are discarded; hydrating them populates the associations on the managed instances passed in.
     *
     * @param Company[] $companies
     */
    private function warmOverviewAssociations(array $companies): void
    {
        $this->createQueryBuilder('c')
            ->addSelect(
                'liveRevision',
                'slogan',
                'description',
                'website',
                'package',
            )
            ->join(
                'c.liveRevision',
                'liveRevision',
            )
            ->join(
                'liveRevision.slogan',
                'slogan',
            )
            ->join(
                'liveRevision.description',
                'description',
            )
            ->join(
                'liveRevision.website',
                'website',
            )
            ->leftJoin(
                'c.packages',
                'package',
            )
            ->where('c IN (:companies)')
            ->setParameter(
                'companies',
                $companies,
            )
            ->getQuery()
            ->getResult();

        $this->getEntityManager()->createQueryBuilder()
            ->select(
                'package',
                'vacancy',
                'liveRevision',
                'name',
                'website',
            )
            ->from(
                CompanyJobPackage::class,
                'package',
            )
            ->leftJoin(
                'package.vacancies',
                'vacancy',
            )
            ->leftJoin(
                'vacancy.liveRevision',
                'liveRevision',
            )
            ->leftJoin(
                'liveRevision.name',
                'name',
            )
            ->leftJoin(
                'liveRevision.website',
                'website',
            )
            ->where('package.company IN (:companies)')
            ->setParameter(
                'companies',
                $companies,
            )
            ->getQuery()
            ->getResult();
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
     * Return the publicly visible company with the given slug, or null when it does not exist or is hidden. The
     * associations the detail page renders are warmed up front to avoid a lazy load per localised text.
     */
    public function findPublicCompanyBySlugName(string $slugName): ?Company
    {
        $company = $this->findCompanyBySlugName($slugName);

        if (
            null === $company
            || $company->isHidden()
        ) {
            return null;
        }

        $this->warmOverviewAssociations([$company]);

        return $company;
    }

    /**
     * Return a company by a given representative's email address.
     */
    public function findCompanyByRepresentativeEmail(string $email): ?Company
    {
        return $this->findOneBy(['representativeEmail' => $email]);
    }
}
