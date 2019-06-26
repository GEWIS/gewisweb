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
     * Get activity proposals within a given period and associated with given organ
     *
     * @param DateTime $begin the date to get the options after
     * @param DateTime $end the date to get the options before
     * @param int $organId the organ options have to be associated with
     * @return array
     */
    public function getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('b')
            ->from('Activity\Model\ActivityCalendarOption', 'a')
            ->andWhere("a.modifiedBy IS NULL")
            ->orWhere("a.status = 'approved'")
            ->from('Activity\Model\ActivityOptionProposal', 'b')
            ->andWhere('a.proposal = b.id')
            ->andWhere('a.beginTime > :begin')
            ->setParameter('begin', $begin)
            ->andWhere('a.beginTime < :end')
            ->setParameter('end', $end)
            ->andWhere('b.organ = :organ')
            ->setParameter('organ', $organId);

        return $qb->getQuery()->getResult();
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
}
