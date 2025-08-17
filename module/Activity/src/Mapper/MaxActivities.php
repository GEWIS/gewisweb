<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\MaxActivities as MaxActivitiesModel;
use Application\Mapper\BaseMapper;
use Override;

/**
 * @template-extends BaseMapper<MaxActivitiesModel>
 */
class MaxActivities extends BaseMapper
{
    /**
     * Finds the MaxActivityOptions model with the given organ and period.
     */
    public function getMaxActivityOptionsByOrganPeriod(
        int $organId,
        int $periodId,
    ): ?MaxActivitiesModel {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->where('m.organ = :organ')
            ->setParameter('organ', $organId)
            ->andWhere('m.period = :period')
            ->setParameter('period', $periodId)
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return MaxActivitiesModel::class;
    }
}
