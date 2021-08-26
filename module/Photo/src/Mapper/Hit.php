<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Photo\Model\Hit as HitModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for Hit.
 */
class Hit extends BaseMapper
{

    /**
     * Get the amount of hits of all photos that have been visited
     * in the specified time range.
     *
     * @param DateTime $begindate
     * @param DateTime $enddate
     *
     * @return array of array of string
     */
    public function getHitsInRange($begindate, $enddate)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('IDENTITY(hit.photo)', 'Count(hit.photo)')
            ->from($this->getRepositoryName(), 'hit')
            ->where('hit.dateTime BETWEEN ?1 AND ?2')
            ->groupBy('hit.photo')
            ->setParameter(1, $begindate)
            ->setParameter(2, $enddate);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return HitModel::class;
    }
}
