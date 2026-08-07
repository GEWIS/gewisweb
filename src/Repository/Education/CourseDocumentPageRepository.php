<?php

declare(strict_types=1);

namespace App\Repository\Education;

use App\Entity\Education\CourseDocumentPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseDocumentPage>
 */
class CourseDocumentPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CourseDocumentPage::class,
        );
    }

    /**
     * Whether any page still points at the given stored path. Page images are content-addressed, so two documents that
     * happen to contain an identical page (a blank sheet, a shared cover) share one stored file.
     */
    public function isPathReferenced(string $path): bool
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('1')
            ->where('p.path = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->setMaxResults(1);

        return null !== $qb->getQuery()->getOneOrNullResult();
    }
}
