<?php

declare(strict_types=1);

namespace App\Repository\Education;

use App\Entity\Education\Course;
use App\Entity\Education\Enums\CourseFilter;
use App\Entity\Education\Enums\CourseSort;
use App\Entity\Education\Exam;
use App\Entity\Education\Summary;
use App\ViewModel\Education\CourseOverviewRow;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function array_map;
use function mb_strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Course::class,
        );
    }

    /**
     * @return Course[]
     */
    public function search(string $query): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.code LIKE ?1')
            ->orWhere('c.name LIKE ?1');
        $qb->setParameter(
            1,
            '%' . addcslashes(
                $query,
                '%_',
            ) . '%',
        );

        return $qb->getQuery()->getResult();
    }

    public function findWithDocuments(string $code): ?Course
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin(
                'c.documents',
                'd',
            )
            ->addSelect('d')
            ->where('c.code = :code')
            ->setParameter(
                'code',
                $code,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Courses are counted rather than hydrated: a row only shows two totals and a date, and loading every document of
     * every course to derive them would fetch the whole archive on every keystroke of the search box.
     *
     * @return CourseOverviewRow[]
     */
    public function findForOverview(
        ?string $query = null,
        CourseFilter $filter = CourseFilter::All,
        CourseSort $sort = CourseSort::Code,
    ): array {
        $qb = $this->overviewQueryBuilder(
            $query,
            $filter,
        );

        match ($sort) {
            CourseSort::Code => $qb->orderBy(
                'c.code',
                'ASC',
            ),
            CourseSort::MostMaterial => $qb->orderBy(
                'documentCount',
                'DESC',
            )->addOrderBy(
                'c.code',
                'ASC',
            ),
            // Courses that never gained anything sort last rather than first, which is what a null date would do.
            CourseSort::RecentlyUpdated => $qb->orderBy(
                'lastAddedAt',
                'DESC',
            )->addOrderBy(
                'c.code',
                'ASC',
            ),
        };

        return array_map(
            static fn (array $row): CourseOverviewRow => new CourseOverviewRow(
                $row['code'],
                $row['name'],
                (int) $row['summaryCount'],
                (int) $row['examCount'],
                null !== $row['lastAddedAt'] ? new DateTime($row['lastAddedAt']) : null,
            ),
            $qb->getQuery()->getArrayResult(),
        );
    }

    /**
     * Courses with nothing are excluded here, unlike in the archive listing: this is a shortcut to something worth
     * reading, not a record of what is missing.
     *
     * @return CourseOverviewRow[]
     */
    public function findWithMostDocuments(int $limit): array
    {
        $qb = $this->overviewQueryBuilder(
            null,
            CourseFilter::All,
        )
            ->having('COUNT(DISTINCT d.id) > 0')
            ->orderBy(
                'documentCount',
                'DESC',
            )
            ->addOrderBy(
                'c.code',
                'ASC',
            )
            ->setMaxResults($limit);

        return array_map(
            static fn (array $row): CourseOverviewRow => new CourseOverviewRow(
                $row['code'],
                $row['name'],
                (int) $row['summaryCount'],
                (int) $row['examCount'],
                null !== $row['lastAddedAt'] ? new DateTime($row['lastAddedAt']) : null,
            ),
            $qb->getQuery()->getArrayResult(),
        );
    }

    /**
     * A link is entered from one side but means the same thing from both, so both directions are read and merged. The
     * old site only ever rendered one of them, which made a link entered the "wrong" way round invisible.
     *
     * @param string[] $codes
     *
     * @return array<string, Course[]>
     */
    public function findSimilarCoursesFor(array $codes): array
    {
        if ([] === $codes) {
            return [];
        }

        $courses = $this->createQueryBuilder('c')
            ->addSelect('similarTo')
            ->addSelect('similarFrom')
            ->leftJoin(
                'c.similarCoursesTo',
                'similarTo',
            )
            ->leftJoin(
                'c.similarCoursesFrom',
                'similarFrom',
            )
            ->where('c.code IN (:codes)')
            ->setParameter(
                'codes',
                $codes,
            )
            ->getQuery()
            ->getResult();

        $similar = [];
        foreach ($courses as $course) {
            $related = $course->getSimilarCourses();

            if ([] === $related) {
                continue;
            }

            $similar[$course->getCode()] = $related;
        }

        return $similar;
    }

    /**
     * @return Paginator<Course>
     */
    public function paginateForAdmin(
        string $search,
        CourseFilter $filter,
        int $page,
        int $pageSize,
    ): Paginator {
        $qb = $this->createQueryBuilder('c')
            ->orderBy(
                'c.code',
                'ASC',
            )
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $search = trim($search);
        if ('' !== $search) {
            $qb->andWhere('c.code LIKE :needle OR LOWER(c.name) LIKE :needle')
                ->setParameter(
                    'needle',
                    '%' . mb_strtolower(addcslashes(
                        $search,
                        '%_',
                    )) . '%',
                );
        }

        match ($filter) {
            CourseFilter::All => null,
            CourseFilter::Empty => $qb->andWhere('SIZE(c.documents) = 0'),
            CourseFilter::WithSummaries => $qb->andWhere($qb->expr()->exists(
                'SELECT 1 FROM ' . Summary::class . ' s WHERE s.course = c',
            )),
            CourseFilter::WithExams => $qb->andWhere($qb->expr()->exists(
                'SELECT 1 FROM ' . Exam::class . ' e WHERE e.course = c',
            )),
        };

        return new Paginator(
            $qb->getQuery(),
            false,
        );
    }

    /**
     * Keyed by code, in one query rather than one per row.
     *
     * @param string[] $codes
     *
     * @return array<string, CourseOverviewRow>
     */
    public function countsFor(array $codes): array
    {
        if ([] === $codes) {
            return [];
        }

        $rows = $this->overviewQueryBuilder(
            null,
            CourseFilter::All,
        )
            ->andWhere('c.code IN (:codes)')
            ->setParameter(
                'codes',
                $codes,
            )
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['code']] = new CourseOverviewRow(
                $row['code'],
                $row['name'],
                (int) $row['summaryCount'],
                (int) $row['examCount'],
                null !== $row['lastAddedAt'] ? new DateTime($row['lastAddedAt']) : null,
            );
        }

        return $counts;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.code)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * How many courses hold at least one document of the given kind, or of any kind when none is given.
     *
     * @phpstan-param class-string<Exam>|class-string<Summary>|null $type
     */
    public function countWithDocuments(?string $type = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.code)')
            ->innerJoin(
                'c.documents',
                'd',
            );

        if (null !== $type) {
            $qb->andWhere('d INSTANCE OF :type')
                ->setParameter(
                    'type',
                    $this->getEntityManager()->getClassMetadata($type),
                );
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Exams and summaries are counted through their own joins rather than a single one over the base class, because DQL
     * cannot tell the two apart inside an aggregate. Each join is left, so a course with nothing still produces a row.
     */
    private function overviewQueryBuilder(
        ?string $query,
        CourseFilter $filter,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('c')
            ->select('c.code AS code')
            ->addSelect('c.name AS name')
            ->addSelect('COUNT(DISTINCT s.id) AS summaryCount')
            ->addSelect('COUNT(DISTINCT e.id) AS examCount')
            ->addSelect('COUNT(DISTINCT d.id) AS documentCount')
            ->addSelect('MAX(d.date) AS lastAddedAt')
            ->leftJoin(
                Summary::class,
                's',
                'WITH',
                's.course = c',
            )
            ->leftJoin(
                Exam::class,
                'e',
                'WITH',
                'e.course = c',
            )
            ->leftJoin(
                'c.documents',
                'd',
            )
            ->groupBy('c.code')
            ->addGroupBy('c.name');

        if (
            null !== $query
            && '' !== trim($query)
        ) {
            $qb->andWhere('c.code LIKE :query OR c.name LIKE :query')
                ->setParameter(
                    'query',
                    '%' . addcslashes(
                        trim($query),
                        '%_',
                    ) . '%',
                );
        }

        match ($filter) {
            CourseFilter::All => null,
            CourseFilter::Empty => $qb->having('COUNT(DISTINCT d.id) = 0'),
            CourseFilter::WithSummaries => $qb->having('COUNT(DISTINCT s.id) > 0'),
            CourseFilter::WithExams => $qb->having('COUNT(DISTINCT e.id) > 0'),
        };

        return $qb;
    }
}
