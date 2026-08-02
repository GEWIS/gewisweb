<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyLabel;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     * Checks whether $vacancySlugName is still free within the given company and category, leaving out the vacancy
     * that is being edited.
     *
     * A slug is unique when no vacancy of the same company and category already uses it. This deliberately does NOT
     * route through {@see self::findVacancy()} (whose `liveRevision` inner join would hide not-yet-approved vacancies
     * and let a pending vacancy collide unseen); it matches on the stable slug columns and resolves the category off
     * the working head ({@see Vacancy::getCurrentRevision()}), where the category now lives.
     */
    public function isSlugNameUnique(
        Company $company,
        string $vacancySlugName,
        VacancyCategories $category,
        ?Vacancy $except = null,
    ): bool {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join(
                'v.package',
                'p',
            )
            ->join(
                'v.currentRevision',
                'cr',
            )
            ->where('p.company = :company')
            ->andWhere('v.slugName = :vacancySlugName')
            ->andWhere('cr.category = :category')
            ->setParameter(
                'company',
                $company->getId(),
                Types::INTEGER,
            )
            ->setParameter(
                'vacancySlugName',
                $vacancySlugName,
            )
            ->setParameter(
                'category',
                $category->value,
            );

        $exceptId = $except?->getId();
        if (null !== $exceptId) {
            $qb->andWhere('v.id != :except')
                ->setParameter(
                    'except',
                    $exceptId,
                    Types::INTEGER,
                );
        }

        return 0 === (int) $qb->getQuery()->getSingleScalarResult();
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
     * What the companies with a running highlight package have put forward, for the career landing page. A pick that
     * is no longer showable drops out on its own, since this is the same "active" predicate the overview uses.
     *
     * @return Vacancy[]
     */
    public function findHighlighted(): array
    {
        $qb = $this->activeVacancyQueryBuilder()
            ->orderBy(
                'c.name',
                'ASC',
            )
            ->addOrderBy(
                'j.id',
                'ASC',
            );

        // An EXISTS rather than a selected join, so a vacancy picked by two packages is still listed once.
        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(
                CompanyHighlightPackage::class,
                'highlight',
            )
            ->join(
                'highlight.vacancies',
                'highlighted',
            )
            ->where('highlighted = j')
            ->andWhere('highlight.published = true')
            ->andWhere('highlight.starts <= :now')
            ->andWhere('highlight.expires > :now');

        return $qb->andWhere($qb->expr()->exists($subQuery->getDQL()))
            ->getQuery()
            ->getResult();
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
            // No start date means the vacancy appears as soon as it is approved; the closing day is always set, and
            // the package's own expiry above caps it regardless.
            ->andWhere('lr.startDate IS NULL OR lr.startDate <= :today')
            ->andWhere('lr.endDate >= :today')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'today',
                new DateTime('today'),
                Types::DATE_MUTABLE,
            );
    }

    /**
     * A page of the administrative overview, narrowed by the optional filters. The status and the category come off
     * the working head rather than the live revision, since a vacancy that has never been approved is precisely the
     * one somebody is looking for here.
     *
     * @return Paginator<Vacancy>
     */
    public function paginateForAdmin(
        string $search,
        ?RevisionStatus $status,
        ?VacancyCategories $category,
        ?int $companyId,
        int $page,
        int $pageSize,
    ): Paginator {
        $qb = $this->createQueryBuilder('v')
            ->addSelect(
                'cr',
                'p',
                'c',
                'name',
            )
            ->join(
                'v.currentRevision',
                'cr',
            )
            ->join(
                'cr.name',
                'name',
            )
            ->join(
                'v.package',
                'p',
            )
            ->join(
                'p.company',
                'c',
            )
            ->orderBy(
                'c.name',
                'ASC',
            )
            ->addOrderBy(
                'v.id',
                'ASC',
            );

        $search = trim($search);
        if ('' !== $search) {
            $qb->andWhere(
                'LOWER(name.valueEN) LIKE :needle'
                . ' OR LOWER(name.valueNL) LIKE :needle'
                . ' OR LOWER(v.slugName) LIKE :needle',
            )
                ->setParameter(
                    'needle',
                    '%' . mb_strtolower($search) . '%',
                );
        }

        if (null !== $status) {
            $qb->andWhere('cr.status = :status')
                ->setParameter(
                    'status',
                    $status->value,
                );
        }

        if (null !== $category) {
            $qb->andWhere('cr.category = :category')
                ->setParameter(
                    'category',
                    $category->value,
                );
        }

        if (null !== $companyId) {
            $qb->andWhere('c.id = :companyId')
                ->setParameter(
                    'companyId',
                    $companyId,
                    Types::INTEGER,
                );
        }

        $qb->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);

        // Nothing to-many is fetch-joined, so the page can be limited in the query itself rather than through the
        // extra round trip that collects the ids first.
        return new Paginator(
            $qb,
            false,
        );
    }

    /**
     * Every vacancy of one company, whatever state it is in, for the company's own overview.
     *
     * @return list<Vacancy>
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType Doctrine getResult() is mixed to Psalm.
     */
    public function findAllForCompany(Company $company): array
    {
        // Both the dashboard and the portal list ask every row whether it is live, which reads the package and the
        // approved revision, so those come along instead of being fetched one row at a time.
        $qb = $this->createQueryBuilder('v')
            ->addSelect(
                'cr',
                'name',
                'p',
                'lr',
            )
            ->join(
                'v.package',
                'p',
            )
            ->join(
                'v.currentRevision',
                'cr',
            )
            ->join(
                'cr.name',
                'name',
            )
            ->leftJoin(
                'v.liveRevision',
                'lr',
            )
            ->where('p.company = :company')
            ->setParameter(
                'company',
                $company->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'v.id',
                'ASC',
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * The vacancies a company may put in a highlight package: its own, currently showable ones. There is no cap per
     * category on purpose, so this is simply everything that is live for the company right now.
     *
     * @return list<Vacancy>
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType Doctrine getResult() is mixed to Psalm.
     */
    public function findHighlightableForCompany(Company $company): array
    {
        return $this->activeVacancyQueryBuilder()
            ->andWhere('c.id = :companyId')
            ->setParameter(
                'companyId',
                $company->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'lr.category',
                'ASC',
            )
            ->addOrderBy(
                'j.id',
                'ASC',
            )
            ->getQuery()
            ->getResult();
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
