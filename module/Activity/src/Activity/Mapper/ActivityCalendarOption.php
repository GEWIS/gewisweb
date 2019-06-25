<?php

namespace Activity\Mapper;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityCalendarOption');
    }

    /**
     * Gets all options created by the given organs
     *
     * @param $organs
     * @param $user
     *
     * @return array
     */
    public function getUpcomingOptionsByOrgans($organs)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->from('Activity\Model\ActivityOptionProposal', 'b')
            ->where('a.proposal = b.id')
            ->where('a.endTime > :now')
            ->andWhere('b.organ IN (:organs)')
            ->orderBy('a.beginTime', 'ASC');

        $qb->setParameter('now', new DateTime())
            ->setParameter('organs', $organs);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by begin date
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
            ->orderBy('a.beginTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere("a.status != 'deleted'");
        }
        $qb->setParameter('now', new DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by begin date
     *
     * @param DateTime $before the date to get the options before
     * @param bool $withDeleted Whether to include deleted options
     * @return array
     */
    public function getPastOptions($before, $withDeleted = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.beginTime < :before')
            ->orderBy('a.beginTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere("a.status != 'deleted'");
        }
        $qb->setParameter('before', $before);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get activity options sorted by begin date within a given period
     *
     * @param DateTime $begin the date to get the options after
     * @param DateTime $end the date to get the options before
     * @param string $status retrieve only options with this status, optional
     * @return array
     */
    public function getOptionsWithinPeriod($begin, $end, $status = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->where('a.beginTime > :begin')
            ->andWhere('a.beginTime < :end')
            ->orderBy('a.beginTime', 'ASC')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end);

        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get activity options sorted by begin date within a given period and associated with given organ
     *
     * @param DateTime $begin the date to get the options after
     * @param DateTime $end the date to get the options before
     * @param int $organId the organ options have to be associated with
     * @param string $status retrieve only options with this status, optional
     * @return array
     */
    public function getOptionsWithinPeriodAndOrgan($begin, $end, $organId, $status = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->from('Activity\Model\ActivityOptionProposal', 'b')
            ->where('a.proposal = b.id')
            ->where('a.beginTime > :begin')
            ->andWhere('a.beginTime < :end')
            ->andWhere('b.organ = :organ')
            ->orderBy('a.beginTime', 'ASC')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('organ', $organId);

        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves options associated with a proposal
     *
     * @param int $proposal
     *
     * @return array
     */
    public function findOptionsByProposal($proposalId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->andWhere('a.proposal = :proposal')
            ->setParameter('proposal', $proposalId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves options associated with a proposal and associated with given organ
     *
     * @param int $proposal
     * @param int $organId the organ proposals have to be associated with
     *
     * @return array
     */
    public function findOptionsByProposalAndOrgan($proposalId, $organId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->andWhere('a.proposal = :proposal')
            ->setParameter('proposal', $proposalId)
            ->setParameter('organ', $organId);

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
