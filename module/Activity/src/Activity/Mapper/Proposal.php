<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

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
     * @return \Activity\Model\ActivityUpdateProposal
     */
    public function getProposalById($id)
    {
        return $this->getRepository()->find($id);
    }
    
    
    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityUpdateProposal');
    }
}