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
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function array_map;
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
     * Search for courses.
     *
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

    /**
     * A course with its documents already loaded, for the course page.
     */
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
     * The rows shown in the archive overview, narrowed and ordered as asked.
     *
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
     * The courses with the most material, for the overview's "most material" panel and the codes offered as popular
     * searches. Courses with nothing are excluded here, unlike in the archive listing: this panel is a shortcut to
     * something worth reading, not a record of what is missing.
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
     * The courses manually linked as similar to each of the given codes, keyed by the code they belong to.
     *
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
     * cannot tell the two apart inside an aggregate. Each join is left, so a course with nothing still produces a row —
     * the archive lists what it is missing.
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
