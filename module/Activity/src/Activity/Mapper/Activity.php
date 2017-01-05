<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;
use \Activity\Model\Activity as ActivityModel;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;
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
     * Gets upcoming activities of the given organs or user, sorted by date.
     *
     * @param array|null $organs
     * @param int|null $userid
     * @param int|null $status An optional filter for activity status
     * @return array
     */
    public function getUpcomingActivitiesByOrganizer($organs=null, $userid=null, $status = null)
    {
        $qb = $this->activityByOrganizerQuery(
                $this->em->createQueryBuilder()->expr()->gt('a.endTime', ':now'),
                $organs,
                $userid,
                $status);

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
                $status);

        return new DoctrineAdapter(new ORMPaginator($qb));
    }

    protected function activityByOrganizerQuery($filter, $organs, $userid, $status)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a');
        if (!is_null($status)){
            $qb->where('a.status = :status')
                ->setParameter('status', $status);
        }
        else{
            $qb->where('a.status <> :status')
                ->setParameter('status', ActivityModel::STATUS_UPDATE);
        }

        if (!is_null($filter)){
            $qb->andWhere($filter)
                ->setParameter('now', new \DateTime());
        }

        $qb->join('a.creator','u');

        if (!is_null($organs) && !is_null($userid)){
            $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->in('a.organ', ':organs'),
                    'u.lidnr = :userid')
                    )
                ->setParameter('organs', $organs)
                ->setParameter('userid', $userid);
        }

        $qb->orderBy('a.beginTime', 'DESC');

        return $qb;
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
}
