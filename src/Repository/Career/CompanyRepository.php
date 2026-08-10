<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyJobPackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function array_map;
use function intval;
use function mb_strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    private const string PUBLIC_SOURCE = <<<'QUERY'
        FROM `Company` AS `t1`
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
        QUERY;

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
        $source = self::PUBLIC_SOURCE;

        $sql = <<<QUERY
            SELECT {$select} {$source}
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
     * The ids of the public companies matching the overview's search box. The order is pinned rather than left to
     * the database: the overview seeds a shuffle over this list and pages through it, which only holds together if the
     * same seed always meets the same list.
     *
     * @return int[]
     */
    public function findPublicIds(string $search): array
    {
        $source = self::PUBLIC_SOURCE;
        $parameters = [];

        $search = trim($search);
        if ('' !== $search) {
            $source .= ' AND (LOWER(`t1`.`name`) LIKE :search OR LOWER(`t1`.`slugName`) LIKE :search)';
            $parameters['search'] = '%' . mb_strtolower($search) . '%';
        }

        $sql = <<<QUERY
            SELECT `t1`.`id` {$source}
            ORDER BY `t1`.`name` ASC
            QUERY;

        return array_map(
            'intval',
            $this->getEntityManager()->getConnection()->fetchFirstColumn(
                $sql,
                $parameters,
            ),
        );
    }

    /**
     * Hydrate the given companies, in the order they are asked for. Ids that no longer resolve to a company are
     * left out.
     *
     * @param int[] $ids
     *
     * @return Company[]
     */
    public function findPublicByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $companies = $this->createQueryBuilder(
            'c',
            'c.id',
        )
            ->where('c.id IN (:ids)')
            ->setParameter(
                'ids',
                $ids,
            )
            ->getQuery()
            ->getResult();

        $this->warmOverviewAssociations($companies);

        $ordered = [];
        foreach ($ids as $id) {
            if (!isset($companies[$id])) {
                continue;
            }

            $ordered[] = $companies[$id];
        }

        return $ordered;
    }

    /**
     * How many companies the public overview would list, for the count the navigation menu carries. The same three
     * conditions as {@see self::findAllPublic()}, counted rather than hydrated, since the menu is on every page.
     */
    public function countPublic(): int
    {
        $source = self::PUBLIC_SOURCE;

        return intval($this->getEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) ' . $source));
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
     * The companies the vacancy overview offers to filter by, keyed by id. Only the name is read there, so the whole
     * list is fetched as rows rather than as entities: the picker is rebuilt on every re-render of the component.
     *
     * @return array<int, string>
     */
    public function findNamesForFilter(): array
    {
        $names = [];

        /** @var array{id: int, name: string} $row */
        foreach (
            $this->createQueryBuilder('c')
                ->select(
                    'c.id',
                    'c.name',
                )
                ->orderBy(
                    'c.name',
                    'ASC',
                )
                ->getQuery()
                ->getArrayResult() as $row
        ) {
            $names[$row['id']] = $row['name'];
        }

        return $names;
    }

    /**
     * The same overview, a page at a time. GEWIS has enough companies on the books for the full list to be unwieldy,
     * so the administrative overview pages through it.
     *
     * @return Paginator<Company>
     */
    public function paginateForAdmin(
        string $search,
        int $page,
        int $pageSize,
    ): Paginator {
        // The row shows the state of the working head and counts the packages, and whether a company is shown at all
        // is worked out from those packages too, so both come along rather than being fetched once per row.
        $qb = $this->createQueryBuilder('c')
            ->addSelect(
                'cr',
                'p',
            )
            ->leftJoin(
                'c.currentRevision',
                'cr',
            )
            ->leftJoin(
                'c.packages',
                'p',
            )
            ->orderBy(
                'c.name',
                'ASC',
            );

        $search = trim($search);
        if ('' !== $search) {
            $qb->andWhere('LOWER(c.name) LIKE :needle OR LOWER(c.slugName) LIKE :needle')
                ->setParameter(
                    'needle',
                    '%' . mb_strtolower($search) . '%',
                );
        }

        $qb->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);

        return new Paginator($qb);
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
}
