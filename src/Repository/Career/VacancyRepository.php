<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyLabel;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function mb_strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<Vacancy>
 */
class VacancyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Vacancy::class,
        );
    }

    /**
     * Checks whether $vacancySlugName is still free within the given company and category.
     *
     * A slug is unique when no vacancy of the same company and category already uses it. This deliberately does NOT
     * route through {@see self::findVacancy()} (whose `liveRevision` inner join would hide not-yet-approved vacancies
     * and let a pending vacancy collide unseen); it matches on the stable slug columns and resolves the category off
     * the working head ({@see Vacancy::getCurrentRevision()}), where the category now lives.
     */
    public function isSlugNameUnique(
        string $companySlugName,
        string $vacancySlugName,
        VacancyCategories $category,
    ): bool {
        $count = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join(
                'v.package',
                'p',
            )
            ->join(
                'p.company',
                'c',
            )
            ->join(
                'v.currentRevision',
                'cr',
            )
            ->where('c.slugName = :companySlugName')
            ->andWhere('v.slugName = :vacancySlugName')
            ->andWhere('cr.category = :category')
            ->setParameter(
                'companySlugName',
                $companySlugName,
            )
            ->setParameter(
                'vacancySlugName',
                $vacancySlugName,
            )
            ->setParameter(
                'category',
                $category->value,
            )
            ->getQuery()
            ->getSingleScalarResult();

        return 0 === (int) $count;
    }

    /**
     * Find all vacancies identified by $vacancySlugName that are owned by a company
     * identified with $companySlugName.
     *
     * The category lives on the live (approved) revision, so category filtering joins through it.
     *
     * @return Vacancy[]
     */
    public function findVacancy(
        ?VacancyCategories $category = null,
        ?int $vacancyLabelId = null,
        ?string $vacancySlugName = null,
        ?string $companySlugName = null,
    ): array {
        $qb = $this->createQueryBuilder('j');
        $qb->join(
            'j.package',
            'p',
        )
            ->addSelect('p')
            ->join(
                'p.company',
                'c',
            )
            ->addSelect('c')
            ->join(
                'j.liveRevision',
                'lr',
            )
            ->addSelect('lr');

        if (null !== $category) {
            $qb->andWhere('lr.category = :category')
                ->setParameter(
                    'category',
                    $category->value,
                );
        }

        if (null !== $vacancyLabelId) {
            $qb->join(
                'lr.labels',
                'l',
            )
                ->andWhere('l.id = :vacancyLabelId')
                ->setParameter(
                    'vacancyLabelId',
                    $vacancyLabelId,
                );
        }

        if (null !== $vacancySlugName) {
            $qb->andWhere('j.slugName = :vacancySlugName')
                ->setParameter(
                    'vacancySlugName',
                    $vacancySlugName,
                );
        }

        if (null !== $companySlugName) {
            $qb->andWhere('c.slugName=:companySlugName')
                ->setParameter(
                    'companySlugName',
                    $companySlugName,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the publicly visible vacancies for the public overview, narrowed by the optional filters (category, owning
     * company, assigned labels and a free-text search over the localised name).
     *
     * This expresses the full "active" predicate ({@see Vacancy::isActive()}) in the query so the filters apply at the
     * database level: the vacancy and its package must be published and the package within its active window, and the
     * owning company must be published with an approved revision (an active package implies the company has a
     * non-expired one, so {@see \App\Entity\Career\Company::isHidden()} reduces to those two checks here). Every
     * association the card renders is fetch-joined to keep the page free of per-item lazy loads.
     *
     * @param int[] $labelIds
     *
     * @return Vacancy[]
     */
    public function findForOverview(
        ?VacancyCategories $category = null,
        ?string $companySlugName = null,
        array $labelIds = [],
        string $search = '',
    ): array {
        $qb = $this->activeVacancyQueryBuilder()
            ->orderBy(
                'c.name',
                'ASC',
            )
            ->addOrderBy(
                'j.id',
                'ASC',
            );

        if (null !== $category) {
            $qb->andWhere('lr.category = :category')
                ->setParameter(
                    'category',
                    $category->value,
                );
        }

        if (null !== $companySlugName) {
            $qb->andWhere('c.slugName = :companySlugName')
                ->setParameter(
                    'companySlugName',
                    $companySlugName,
                );
        }

        if ([] !== $labelIds) {
            // Filter through an EXISTS subquery rather than a selected join, so the vacancy's own labels collection is
            // still hydrated in full for display (a filtering join would prune it to the matched labels).
            $subQuery = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(
                    VacancyLabel::class,
                    'filterLabel',
                )
                ->join(
                    'filterLabel.revisions',
                    'filterRevision',
                )
                ->where('filterRevision = lr')
                ->andWhere('filterLabel.id IN (:labelIds)');

            $qb->andWhere($qb->expr()->exists($subQuery->getDQL()))
                ->setParameter(
                    'labelIds',
                    $labelIds,
                );
        }

        if ('' !== trim($search)) {
            $qb->andWhere('LOWER(name.valueEN) LIKE :search OR LOWER(name.valueNL) LIKE :search')
                ->setParameter(
                    'search',
                    '%' . mb_strtolower(trim($search)) . '%',
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a single publicly visible vacancy by its company, category and slug (the tuple that identifies it in a URL),
     * or null when it does not exist or is not currently active. Shares the "active" predicate and the fetch joins with
     * {@see self::findForOverview()}, so the detail page renders without lazy loads.
     */
    public function findPublicVacancy(
        string $companySlugName,
        VacancyCategories $category,
        string $vacancySlugName,
    ): ?Vacancy {
        $result = $this->activeVacancyQueryBuilder()
            ->andWhere('c.slugName = :companySlugName')
            ->andWhere('lr.category = :category')
            ->andWhere('j.slugName = :vacancySlugName')
            ->setParameter(
                'companySlugName',
                $companySlugName,
            )
            ->setParameter(
                'category',
                $category->value,
            )
            ->setParameter(
                'vacancySlugName',
                $vacancySlugName,
            )
            ->getQuery()
            ->getResult();

        return $result[0] ?? null;
    }

    /**
     * The base query for publicly visible ("active") vacancies, with every association the cards and the detail page
     * render fetch-joined. Expresses {@see Vacancy::isActive()} at the database level: the vacancy and its package must
     * be published and the package within its active window, and the owning company published with an approved revision
     * (an active package implies a non-expired one, so {@see \App\Entity\Career\Company::isHidden()} reduces to those
     * two checks here). Callers add their own filters and ordering.
     */
    private function activeVacancyQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('j')
            ->join(
                'j.package',
                'p',
            )
            ->addSelect('p')
            ->join(
                'p.company',
                'c',
            )
            ->addSelect('c')
            // The company's own live revision holds the logo the card renders; join it so getLogo does not lazy-load
            // one revision per distinct company on the overview.
            ->leftJoin(
                'c.liveRevision',
                'clr',
            )
            ->addSelect('clr')
            ->join(
                'j.liveRevision',
                'lr',
            )
            ->addSelect('lr')
            ->join(
                'lr.name',
                'name',
            )
            ->addSelect('name')
            ->join(
                'lr.location',
                'location',
            )
            ->addSelect('location')
            ->join(
                'lr.description',
                'description',
            )
            ->addSelect('description')
            ->join(
                'lr.website',
                'website',
            )
            ->addSelect('website')
            ->leftJoin(
                'lr.labels',
                'label',
            )
            ->addSelect('label')
            ->leftJoin(
                'label.name',
                'labelName',
            )
            ->addSelect('labelName')
            ->where('j.published = true')
            ->andWhere('p.published = true')
            ->andWhere('p.starts <= :now')
            ->andWhere('p.expires > :now')
            ->andWhere('c.published = true')
            ->andWhere('c.liveRevision IS NOT NULL')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );
    }

    public function findByPackageAndCompany(
        string $companySlugName,
        int $packageId,
        int $vacancyId,
    ): ?Vacancy {
        $qb = $this->createQueryBuilder('j');
        $qb->innerJoin(
            'j.package',
            'p',
            'WITH',
            'p.id = :packageId',
        )
            ->innerJoin(
                'p.company',
                'c',
                'WITH',
                'c.slugName = :companySlugName',
            )
            ->where('j.id = :vacancyId')
            ->setParameter(
                'vacancyId',
                $vacancyId,
            )
            ->setParameter(
                'packageId',
                $packageId,
            )
            ->setParameter(
                'companySlugName',
                $companySlugName,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }
}
