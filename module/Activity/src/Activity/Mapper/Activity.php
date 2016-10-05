<?php

namespace Activity\Mapper;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use \Activity\Model\Activity as ActivityModel;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

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
     * @param \Organ\Model\Organ $organ Option organ by whom the activities are organized.
     *
     * @return array
     */
    public function getUpcomingActivities($count = null, $organ = null)
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

        if(!is_null($organ)) {
            $qb->andWhere('a.organ = :organ')
                ->setParameter('organ', $organ);
        }

        $qb->setParameter('now', new \DateTime());
        $qb->setParameter('status', ActivityModel::STATUS_APPROVED);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get an activity paginator by the status of the activity
     * @param integer $status
     * @param integer $page
     * @param integer $perPage
     * @return Paginator
     */
    public function getActivityPaginatorByStatus($status, $page = 1, $perPage = 5)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.status = :status')
            ->orderBy('a.beginTime', 'desc')
            ->setParameters([
                'status' => $status
            ]);

        $resultArray = $qb->getQuery()->getResult();
        $paginator = new Paginator(new ArrayAdapter($resultArray));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($perPage);
        return $paginator;
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

    /**
     * Returns the newest activity that has taken place
     *
     * @return \Activity\Model\Activity
     */
    public function getNewestActivity()
    {
        $qb = $this->getArchivedActivityQueryBuilder()
            ->setMaxResults(1)
            ->orderBy('a.beginTime', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the oldest activity that has taken place
     *
     * @return \Activity\Model\Activity
     */
    public function getOldestActivity()
    {
        $qb = $this->getArchivedActivityQueryBuilder()
            ->setMaxResults(1)
            ->orderBy('a.beginTime', 'ASC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Create a query that is restricted to finished activities which are displayed in the activity.
     *
     * Finished activities do have the following constaints (1) The begin time is less than the current time and
     * (2) it must have been approved before
     *
     * @return QueryBuilder
     */
    protected function getArchivedActivityQueryBuilder()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->andWhere('a.status = :status')
            ->andWhere('a.beginTime IS NOT NULL');
        $qb->setParameter('status', ActivityModel::STATUS_APPROVED);

        return $qb;
    }
}
