<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;
use \Activity\Model\Activity as ActivityModel;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ActivityCalendarOption
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
     * Get upcoming activity options sorted by creation date
     *
     * @return array
     */
    public function getUpcomingOptions()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.endTime > :now')
            ->andWhere('a.deletedBy IS NULL')
            ->orderBy('a.creationTime', 'ASC');

        $qb->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist an option
     *
     * @param \Activity\Model\ActivityCalendarOption $option
     */
    public function persist($option)
    {
        $this->em->persist($option);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }
}
