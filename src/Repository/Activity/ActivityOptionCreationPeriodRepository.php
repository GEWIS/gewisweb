<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityOptionCreationPeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityOptionCreationPeriod>
 */
class ActivityOptionCreationPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityOptionCreationPeriod::class,
        );
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that is currently active.
     *
     * @return ActivityOptionCreationPeriod[]
     */
    public function getCurrentActivityOptionCreationPeriods(): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.beginPlanningTime < CURRENT_TIMESTAMP()')
            ->andWhere('o.endPlanningTime > CURRENT_TIMESTAMP()')
            ->orderBy(
                'o.beginPlanningTime',
                'ASC',
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that will be active next.
     *
     * @return ActivityOptionCreationPeriod[]
     */
    public function getUpcomingActivityOptionCreationPeriods(): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.beginPlanningTime > CURRENT_TIMESTAMP()')
            ->orderBy(
                'o.beginPlanningTime',
                'ASC',
            );

        return $qb->getQuery()->getResult();
    }
}
