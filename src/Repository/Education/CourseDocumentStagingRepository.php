<?php

declare(strict_types=1);

namespace App\Repository\Education;

use App\Entity\Education\CourseDocumentStaging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseDocumentStaging>
 */
class CourseDocumentStagingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CourseDocumentStaging::class,
        );
    }

    /**
     * Oldest first, so a batch is worked through in the order it arrived.
     *
     * @return CourseDocumentStaging[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy(
                's.uploadedAt',
                'ASC',
            )
            ->addOrderBy(
                's.id',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Whether any staged upload still points at the given stored path. Uploads are content-addressed, so the same PDF
     * staged twice is one stored file.
     */
    public function isPathReferenced(string $path): bool
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('1')
            ->where('s.path = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->setMaxResults(1);

        return null !== $qb->getQuery()->getOneOrNullResult();
    }
}
