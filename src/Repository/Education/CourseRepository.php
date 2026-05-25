<?php

declare(strict_types=1);

namespace App\Repository\Education;

use App\Entity\Education\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;

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
}
