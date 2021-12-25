<?php

namespace Activity\Mapper;

use Activity\Model\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel;
use Application\Mapper\BaseMapper;
use DateTime;
use Exception;

class ActivityOptionCreationPeriod extends BaseMapper
{
    /**
     * Finds the ActivityOptionCreationPeriod model that is currently active.
     *
     * @return ActivityOptionCreationPeriodModel|null
     *
     * @throws Exception
     */
    public function getCurrentActivityOptionCreationPeriod(): ?ActivityOptionCreationPeriodModel
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.beginPlanningTime < :today')
            ->andWhere('o.endPlanningTime > :today')
            ->orderBy('o.beginPlanningTime', 'ASC')
            ->setParameter('today', new DateTime())
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that will be active next.
     *
     * @return ActivityOptionCreationPeriodModel|null
     *
     * @throws Exception
     */
    public function getUpcomingActivityOptionCreationPeriod(): ?ActivityOptionCreationPeriodModel
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.beginPlanningTime > :today')
            ->orderBy('o.beginPlanningTime', 'ASC')
            ->setParameter('today', new DateTime())
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityOptionCreationPeriodModel::class;
    }
}
