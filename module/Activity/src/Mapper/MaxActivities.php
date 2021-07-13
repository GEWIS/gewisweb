<?php

namespace Activity\Mapper;

use Activity\Model\MaxActivities as MaxActivitiesModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class MaxActivities
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Finds the MaxActivityOptions model with the given id.
     *
     * @param int $id
     *
     * @return MaxActivitiesModel
     */
    public function getMaxActivityOptionsById($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\MaxActivities');
    }

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
}
