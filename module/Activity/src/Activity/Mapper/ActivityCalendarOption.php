<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

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
     * Find an option by its id
     *
     * @param int $optionId Option id
     *
     * @return \Activity\Model\ActivityCalendarOption
     */
    public function find($optionId)
    {
        return $this->getRepository()->findOneBy(['id' => $optionId]);
    }

    /**
     * Gets all options created by the given organs or user
     *
     * @param $organs
     * @param $user
     *
     * @return array
     */
    public function getUpcomingOptionsByOrganOrUser($organs, $user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.endTime > :now')
            ->andWhere()
            ->orderBy('a.creationTime', 'ASC');

        $qb->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
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

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityCalendarOption');
    }
}
