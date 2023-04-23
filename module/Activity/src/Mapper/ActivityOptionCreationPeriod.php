<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel;
use Application\Mapper\BaseMapper;
use DateTime;
use Exception;

/**
 * @template-extends BaseMapper<ActivityOptionCreationPeriodModel>
 */
class ActivityOptionCreationPeriod extends BaseMapper
{
    /**
     * Finds the ActivityOptionCreationPeriod model that is currently active.
     *
     * @return array<array-key, ActivityOptionCreationPeriodModel>
     */
    public function getCurrentActivityOptionCreationPeriods(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.beginPlanningTime < :today')
            ->andWhere('o.endPlanningTime > :today')
            ->orderBy('o.beginPlanningTime', 'ASC')
            ->setParameter('today', new DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that will be active next.
     *
     * @return array<array-key, ActivityOptionCreationPeriodModel>
     */
    public function getUpcomingActivityOptionCreationPeriods(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.beginPlanningTime > :today')
            ->orderBy('o.beginPlanningTime', 'ASC')
            ->setParameter('today', new DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityOptionCreationPeriodModel::class;
    }
}
