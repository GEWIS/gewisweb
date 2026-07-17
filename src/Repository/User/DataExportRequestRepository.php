<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\DataExportRequest;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DataExportRequest>
 */
class DataExportRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            DataExportRequest::class,
        );
    }

    public function findLatestForUser(User $user): ?DataExportRequest
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter(
                'user',
                $user->getLidnr(),
            )
            ->orderBy(
                'r.requestedAt',
                'DESC',
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
