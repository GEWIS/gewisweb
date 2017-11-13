<?php

namespace Activity\Mapper;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use \Activity\Model\Activity as ActivityModel;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

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
    public function getUpcomingActivities($count = null, $organ = null, $category = null)
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

        // For now 'career' is the only category, however this may change in the future
        if ($category === 'career') {
            $qb->andWhere('a.isMyFuture = 1');
        }
        $qb->setParameter('now', new \DateTime());
        $qb->setParameter('status', ActivityModel::STATUS_APPROVED);

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets upcoming activities of the given organs or user, sorted by date.
     *
     * @param array|null $organs
     * @param int|null $userid
     * @param int|null $status An optional filter for activity status
     * @return array
     */
    public function getAllUpcomingActivities($organs = null, $userid = null, $status = null)
    {
        $qb = $this->activityByOrganizerQuery(
            $this->em->createQueryBuilder()->expr()->gt('a.endTime', ':now'),
            $organs,
            $userid,
            $status
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets a paginator of old activities of the given organs, sorted by date.
     * Supplying 'null' to all arguments gets all activities
     *
     * @param array|null $organs
     * @param int|null $userid
     * @param int|null $status An optional filter for activity status
     * @return array
     */
    public function getOldActivityPaginatorAdapterByOrganizer($organs = null, $userid = null, $status = null)
    {
        $qb = $this->activityByOrganizerQuery(
            $this->em->createQueryBuilder()->expr()->lt('a.endTime', ':now'),
            $organs,
            $userid,
            $status
        );

        return new DoctrineAdapter(new ORMPaginator($qb));
    }

    protected function activityByOrganizerQuery($filter, $organs, $userid, $status)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a');
        if (!is_null($status)) {
            $qb->where('a.status = :status')
                ->setParameter('status', $status);
        } else {
            $qb->where('a.status <> :status')
                ->setParameter('status', ActivityModel::STATUS_UPDATE);
        }

        if (!is_null($filter)) {
            $qb->andWhere($filter)
                ->setParameter('now', new \DateTime());
        }

        $qb->join('a.creator', 'u');

        if (!is_null($organs) && !is_null($userid)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->in('a.organ', ':organs'),
                'u.lidnr = :userid'
            ))
                ->setParameter('organs', $organs)
                ->setParameter('userid', $userid);
        }

        $qb->orderBy('a.beginTime', 'DESC');

        return $qb;
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
            ->where('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
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
            ->where('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
            ->setMaxResults(1)
            ->orderBy('a.beginTime', 'ASC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    public function getArchivedActivitiesInRange($start, $end)
    {
        $qb = $this->getArchivedActivityQueryBuilder()
            ->andWhere('a.beginTime >= :start')
            ->setParameter('start', $start)
            ->andWhere('a.endTime <= :end')
            ->setParameter('end', $end)
            ->andWhere('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
            ->orderBy('a.beginTime', 'ASC');

        return $qb->getQuery()->getResult();
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
