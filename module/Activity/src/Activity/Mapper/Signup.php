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

    public function isSignedUp($activityId, $userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivitySignup', 'a')
            ->where('a.user_id = ?1')
            ->andWhere('a.activity_id = ?2')
            ->setParameters([
                1 => $userId,
                2 => $activityId
            ]);
        $result = $qb->getQuery()->getResult();
        return count($result) != 0;
    }

}
