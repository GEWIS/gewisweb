<?php

namespace Activity\Mapper;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Option\Model\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel;

class ActivityOptionCreationPeriod
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Finds the ActivityOptionCreationPeriod model with the given id.
     *
     * @param int $id
     * @return ActivityOptionCreationPeriodModel
     */
    public function getActivityOptionCreationPeriodById($id)
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
        return $this->em->getRepository('Activity\Model\ActivityOptionCreationPeriod');
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that is currently active
     *
     * @return ActivityOptionCreationPeriod
     * @throws Exception
     */
    public function getCurrentActivityOptionCreationPeriod()
    {
        $qb = $this->em->createQueryBuilder();
        $today = new DateTime();
        $qb->select('x')
            ->from('Activity\Model\ActivityOptionCreationPeriod', 'x')
            ->andWhere('x.beginPlanningTime < :today')
            ->andWhere('x.endPlanningTime > :today')
            ->orderBy('x.beginPlanningTime', 'ASC')
            ->setParameter('today', $today)
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Finds the ActivityOptionCreationPeriod model that will be active next
     *
     * @return ActivityOptionCreationPeriod
     * @throws Exception
     */
    public function getUpcomingActivityOptionCreationPeriod()
    {
        $qb = $this->em->createQueryBuilder();
        $today = new DateTime();
        $qb->select('x')
            ->from('Activity\Model\ActivityOptionCreationPeriod', 'x')
            ->where('x.beginPlanningTime > :today')
            ->orderBy('x.beginPlanningTime', 'ASC')
            ->setParameter('today', $today)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
