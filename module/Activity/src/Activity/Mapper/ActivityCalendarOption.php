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
            ->andWhere('a.creator = :user OR a.organ IN (:organs)')
            ->orderBy('a.creationTime', 'ASC');

        $qb->setParameter('now', new \DateTime())
            ->setParameter('user', $user)
            ->setParameter('organs', $organs);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by creation date
     *
     * @param bool $withDeleted whether to include deleted results
     * @return array
     */
    public function getUpcomingOptions($withDeleted = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.endTime > :now')
            ->orderBy('a.creationTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere('a.deletedBy IS NULL');
        }
        $qb->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by creation date
     *
     * @param \DateTime $before the date to get the options before
     * @param bool $withDeleted Whether to include deleted options
     * @return array
     */
    public function getPastOptions($before, $withDeleted = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.creationTime < :before')
            ->orderBy('a.creationTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere('a.deletedBy IS NULL');
        }
        $qb->setParameter('before', $before);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves upcoming, non-deleted options by name
     *
     * @param $name
     *
     * @return array
     */
    public function findOptionsByName($name)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.endTime > :now')
            ->andWhere('a.name LIKE :name')
            ->andWhere('a.deletedBy IS NULL')
            ->setParameter('now', new \DateTime())
            ->setParameter('name', '%' . $name . '%');

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
