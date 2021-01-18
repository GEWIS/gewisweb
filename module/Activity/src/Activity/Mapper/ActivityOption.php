<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class ActivityOption
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
     * Finds the option of with the given id.
     * 
     * @param int $id
     * @return \Activity\Model\ActivityOption
     */
    public function getOptionById($id)
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
        return $this->em->getRepository('Activity\Model\ActivityOption');
    }
}