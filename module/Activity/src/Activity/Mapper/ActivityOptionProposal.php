<?php

namespace Activity\Mapper;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Option\Model\ActivityOptionProposal as ActivityOptionProposalModel;

class ActivityOptionProposal
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
     * Finds the ActivityOptionProposal model with the given id.
     *
     * @param int $id
     * @return ActivityOptionProposalModel
     */
    public function getActivityOptionProposalById($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityOptionProposal');
    }

    /**
     * Get activity proposals within a given period and associated with given organ
     *
     * @param DateTime $begin the date to get the options after
     * @param DateTime $end the date to get the options before
     * @param int $organ_id the organ options have to be associated with
     * @param string $status retrieve only options with this status, optional
     * @return array
     */
    public function getOptionsWithinPeriodAndOrgan($begin, $end, $organ_id, $status = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('b')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->join('Activity\Model\ActivityOptionProposal', 'b')
            ->where('a.beginTime > :begin')
            ->where('a.beginTime < :end')
            ->where('b.organ = :organ')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('organ', $organ_id);

        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get activity proposals within a given period and associated with given organ
     *
     * @param DateTime $begin the date to get the options after
     * @param DateTime $end the date to get the options before
     * @param int $organ_id the organ options have to be associated with
     * @return array
     */
    public function getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organ_id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('b')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->join('Activity\Model\ActivityOptionProposal', 'b')
            ->where('a.beginTime > :begin')
            ->where('a.beginTime < :end')
            ->where('b.organ = :organ')
            ->where("a.status != 'deleted'")
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('organ', $organ_id);

        return $qb->getQuery()->getResult();
    }
}
