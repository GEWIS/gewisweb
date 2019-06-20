<?php

namespace Activity\Mapper;

use Activity\Model\ActivityUpdateProposal;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class Proposal
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
     * Finds the proposal of with the given id.
     *
     * @param int $id
     * @return ActivityUpdateProposal
     */
    public function getProposalById($id)
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
        return $this->em->getRepository('Activity\Model\ActivityUpdateProposal');
    }

    /**
     * Finds all update proposals.
     *
     * @return Collection of \Activity\Model\ActivityUpdateProposal
     */
    public function getAllProposals()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityUpdateProposal', 'a');
        return $qb->getQuery()->getResult();
    }
}
