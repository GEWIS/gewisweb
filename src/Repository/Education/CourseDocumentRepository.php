<?php

declare(strict_types=1);

namespace App\Repository\Education;

use App\Entity\Education\Course;
use App\Entity\Education\CourseDocument;
use App\Entity\Education\Exam;
use App\Entity\Education\Summary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseDocument>
 */
class CourseDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CourseDocument::class,
        );
    }

    /**
     * @phpstan-param class-string<Exam>|class-string<Summary> $type
     *
     * @return CourseDocument[]
     */
    public function findDocumentsByCourse(
        Course $course,
        string $type,
    ): array {
        $qb = $this->createQueryBuilder('d');
        $qb->where('d.course = :course')
            ->andWhere('d INSTANCE OF :type')
            ->setParameter(
                'course',
                $course,
                Course::class,
            )
            ->setParameter(
                'type',
                $this->getEntityManager()->getClassMetadata($type),
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * The most recently dated documents in the archive, for the overview's "recently added" panel.
     *
     * Ordered by the date on the document rather than when it was uploaded: a member browsing for material cares which
     * exam is the newest, not which one an administrator happened to file last.
     *
     * @return CourseDocument[]
     */
    public function findRecent(int $limit): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin(
                'd.course',
                'c',
            )
            ->addSelect('c')
            ->orderBy(
                'd.date',
                'DESC',
            )
            ->addOrderBy(
                'd.id',
                'DESC',
            )
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Whether any document still points at the given stored path. Uploads are content-addressed, so the same PDF filed
     * under two courses is one stored file.
     */
    public function isPathReferenced(string $path): bool
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('1')
            ->where('d.path = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->setMaxResults(1);

        return null !== $qb->getQuery()->getOneOrNullResult();
    }
}
