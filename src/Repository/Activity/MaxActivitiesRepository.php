<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\MaxActivities;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaxActivities>
 */
class MaxActivitiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MaxActivities::class,
        );
    }

    /**
     * Finds the MaxActivityOptions model with the given organ and period.
     */
    public function getMaxActivityOptionsByOrganPeriod(
        int $organId,
        int $periodId,
    ): ?MaxActivities {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.organ = :organ')
            ->setParameter(
                'organ',
                $organId,
            )
            ->andWhere('m.period = :period')
            ->setParameter(
                'period',
                $periodId,
            )
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }
}
