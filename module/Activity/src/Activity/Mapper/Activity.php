<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;
use \Activity\Model\Activity as ActivityModel;

class Activity
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
     * @param $id
     *
     * @return array
     */
    public function getActivityById($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $id);
        $result = $qb->getQuery()->getResult();

        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * get all activities including options.
     *
     * @return array
     */
    public function getAllActivities()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date
     *
     * @param integer $count Optional number of activities to retrieve.
     *
     * @return array
     */
    public function getUpcomingActivities($count = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.endTime > :now')
            ->andWhere('a.status = :status')
            ->orderBy('a.beginTime', 'ASC');

        if(!is_null($count)) {
            $qb->setMaxResults($count);
        }

        $qb->setParameter('now', new \DateTime());
        $qb->setParameter('status', ActivityModel::STATUS_APPROVED);

        return $qb->getQuery()->getResult();
    }

    /**
     * get all activities including options.
     *
     * @return array
     */
    public function getApprovedActivities()
    {
        return $this->getActivitiesByStatus(ActivityModel::STATUS_APPROVED);
    }

    /**
     * Get all disapproved activitiesa.
     *
     * @return array
     */
    public function getDisapprovedActivities()
    {
        return $this->getActivitiesByStatus(ActivityModel::STATUS_DISAPPROVED);
    }

    /**
     * Get all the unapproved activities
     *
     * @return array
     */
    public function getUnapprovedActivities()
    {
        return $this->getActivitiesByStatus(ActivityModel::STATUS_TO_APPROVE);
    }

    /**
     * Get all the activities with a specific status
     *
     * @param integer $status
     * @return array
     */
    protected function getActivitiesByStatus($status)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.status = :status')
            ->orderBy('a.beginTime', 'DESC');

        $qb->setParameter('status', $status);

        return $qb->getQuery()->getResult();
    }
}
