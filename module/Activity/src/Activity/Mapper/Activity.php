<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

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
     * get all activities including options.
     *
     * @return array
     */
    public function getAllApproved()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Activity', 'a')
            ->where('a.approved = 1');

        return $qb->getQuery()->getResult();
    }
}
