<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class Signup
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
     * @param int $id
     * @return \Activity\Model\ActivitySignup
     */
    public function getSignupById($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivitySignup', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $id);
        $result = $qb->getQuery()->getResult();

        return count($result) > 0 ? $result[0] : null;
    }


    /**
     * Check if a user is signed up for an activity.
     *
     * @param $activityId
     * @param $userId
     *
     * @return bool
     */
    public function isSignedUp($activityId, $userId)
    {
        return $this->getSignUp($activityId, $userId) !== null;
    }

    /**
     * Get the signup object for an usedid/activityid if it exists.
     *
     * @param int $activityId
     * @param int $userId
     *
     * @return \Activity\Model\ActivitySignup
     */
    public function getSignUp($activityId, $userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\UserActivitySignup', 'a')
            ->join('a.user', 'u')
            ->where('u.lidnr = ?1')
            ->join('a.activity', 'ac')
            ->andWhere('ac.id = ?2')
            ->setParameters([
                1 => $userId,
                2 => $activityId,
            ]);
        $result = $qb->getQuery()->getResult();

        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * Get all activities which a user is signed up for.
     *
     * @param int $userId
     *
     * @return \Activity\Model\ActivitySignup
     */
    public function getSignedUpActivities($userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\UserActivitySignup', 'a')
            ->join('a.user', 'u')
            ->where('u.lidnr = ?1')
            ->setParameter(1, $userId);
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    public function getNumberOfSignedUpMembers($activityId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(s)')
           ->from('Activity\Model\UserActivitySignup', 's')
           ->join('s.activity', 'a')
           ->where('a.id = ?1')
           ->setParameter(1, $activityId);
        $result = $qb->getQuery()->getResult();

        return $result[0];
    }
}
