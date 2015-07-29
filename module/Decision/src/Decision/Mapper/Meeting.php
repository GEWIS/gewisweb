<?php

namespace Decision\Mapper;

use Decision\Model\Meeting as MeetingModel;
use Doctrine\ORM\EntityManager;

class Meeting
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
     * Find all meetings.
     *
     * @return array Of all meetings
     */
    public function findAll()
    {
        $qb = $this->getRepository()->createQueryBuilder('m');

        $qb->orderBy('m.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist a meeting model.
     *
     * @param MeetingModel $meeting Meeting to persist.
     */
    public function persist(MeetingModel $meeting)
    {
        $this->em->persist($meeting);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Meeting');
    }
}
