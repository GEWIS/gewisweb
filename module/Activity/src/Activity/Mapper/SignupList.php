<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class SignupList
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
     * @param $signupListId
     * @param $activityId
     *
     * @return array
     */
    public function getSignupListByIdAndActivity($signupListId, $activityId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\SignupList', 'a')
            ->where('a.id = :signupList')
            ->andWhere('a.activity = :activity')
            ->setParameter('signupList', $signupListId)
            ->setParameter('activity', $activityId);
        $result = $qb->getQuery()->getResult();

        return count($result) > 0 ? $result[0] : null;
    }

    public function getSignupListsOfActivity($activityId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\SignupList', 'a')
            ->where('a.activity = :activity')
            ->setParameter('activity', $activityId);
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
