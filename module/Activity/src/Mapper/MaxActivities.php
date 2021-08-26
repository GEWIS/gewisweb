<?php

namespace Activity\Mapper;

use Activity\Model\MaxActivities as MaxActivitiesModel;
use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class MaxActivities extends BaseMapper
{
    /**
     * Finds the MaxActivityOptions model with the given organ and period.
     *
     * @param int $organId
     * @param int $periodId
     *
     * @return MaxActivitiesModel
     */
    public function getMaxActivityOptionsByOrganPeriod($organId, $periodId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('x')
            ->from('Activity\Model\MaxActivities', 'x')
            ->andWhere('x.organ = :organ')
            ->setParameter('organ', $organId)
            ->andWhere('x.period = :period')
            ->setParameter('period', $periodId)
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return MaxActivitiesModel::class;
    }
}
