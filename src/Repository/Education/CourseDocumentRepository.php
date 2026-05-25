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
     * @psalm-param class-string<Exam>|class-string<Summary> $type
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
}
