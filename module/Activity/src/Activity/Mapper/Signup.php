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
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Check if a user is signed up for an activity
     *
     * @param $activityId
     * @param $userId
     * @return bool
     */
    public function isSignedUp($activityId, $userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivitySignup', 'a')
            ->join('a.user', 'u')
            ->where('u.lidnr = ?1')
            ->join('a.activity', 'ac')
            ->andWhere('ac.id = ?2')
            ->setParameters([
                1 => $userId,
                2 => $activityId
            ]);
        $result = $qb->getQuery()->getResult();
        return count($result) != 0;
    }

    /**
     * Get all the users that are signed up for an activity
     *
     * @param $activityId
     * @return array
     */
	public function getSignedUp($activityId)
    {
        $qb = $this->em->createQueryBuilder();

		//get all users that have signed up for the activity
        $qb->select('ac, a, u, m')
            ->from('Activity\Model\Activity', 'ac')
            ->leftJoin('ac.signUps', 'a')
            ->join('a.user', 'u')
            ->join('u.member', 'm')
            ->where('ac.id = ?1')
            ->setParameters([
                1 => $activityId
            ]);

        /* @var $activity \Activity\Model\Activity */
        $activity = $qb->getQuery()->getResult();

        $members = [];
        /* @var $signUp \Activity\Model\ActivitySignUp*/
        foreach ($activity[0]->get('signUps') as $signUp) {
            $members[] =$signUp->getUser()->getMember();
        }

        return $members;
    }

}
