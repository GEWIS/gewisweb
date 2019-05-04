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
     * Get upcoming activities sorted by date for member
     *
     * @param \User\Model\User $user Option user that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesForMember($user)
    {
        // Get subscriptions (not including non-approved)
        $result = $this->getUpcomingActivitiesSubscribedBy($user);

        // Get created by member (including non-approved)
        $result = array_merge($result, $this->getUpcomingActivitiesCreatedBy($user));

        // Get associated with organs (including non-approved)


        foreach ($user->getMember()->getCurrentOrganInstallations() as $organMember) {
            $result = array_merge($result, $this->getUpcomingActivitiesByOrgan($organMember->getOrgan()));
       }

        // Do sorting based on start time
        usort($result, function ($a, $b) {
            $beginA = $a->getBeginTime();
            $beginB = $b->getBeginTime();
            return $beginA < $beginB ? -1 : 1;
        });

        $size = count($result);

        for ($i = 0; $i < $size; $i++) {
            for ($j = $i + 1; $j < $size; $j++) {
                if (array_key_exists($i, $result) && array_key_exists($j, $result)) {
                    if ($result[$i]->getId() == $result[$j]->getId()) {
                        unset($result[$j]);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get upcoming activities sorted by date that a user is subscribed to
     *
     * @param \User\Model\User $user Option user that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesSubscribedBy($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->from('Activity\Model\UserActivitySignup', 'b')
            ->where('a.endTime > :now')
            ->setParameter('now', new \DateTime())
            ->andWhere('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
            ->andWhere('a = b.activity')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user);
        $result = $qb->getQuery()->getResult();
        return $result;
    }

    /**
     * Get upcoming activities sorted by date that a user created
     *
     * @param \User\Model\User $user Option user that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesCreatedBy($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.endTime > :now')
            ->setParameter('now', new \DateTime())
            ->andWhere('a.creator = :user')
            ->setParameter('user', $user);
        $result = $qb->getQuery()->getResult();
        return $result;
    }

    /**
     * Get upcoming activities sorted by date that a organ created
     *
     * @param \Decision\Model\Organ $organ Option organ that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesByOrgan($organ)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.endTime > :now')
            ->setParameter('now', new \DateTime())
           ->andWhere('a.organ = :organ')
            ->setParameter('organ', $organ->getId());
        $result = $qb->getQuery()->getResult();
        return $result;
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
