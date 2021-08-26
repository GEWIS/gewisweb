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
     * @return ActivityOptionCreationPeriodModel
     *
     * @throws Exception
     */
    public function getCurrentActivityOptionCreationPeriod()
    {
        $qb = $this->em->createQueryBuilder();
        $today = new DateTime();
        $qb->select('x')
            ->from($this->getRepositoryName(), 'x')
            ->andWhere('x.beginPlanningTime < :today')
            ->andWhere('x.endPlanningTime > :today')
            ->orderBy('x.beginPlanningTime', 'ASC')
            ->setParameter('today', $today)
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that will be active next.
     *
     * @return ActivityOptionCreationPeriod
     *
     * @throws Exception
     */
    public function getUpcomingActivityOptionCreationPeriod()
    {
        $qb = $this->em->createQueryBuilder();
        $today = new DateTime();
        $qb->select('x')
            ->from($this->getRepositoryName(), 'x')
            ->where('x.beginPlanningTime > :today')
            ->orderBy('x.beginPlanningTime', 'ASC')
            ->setParameter('today', $today)
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
