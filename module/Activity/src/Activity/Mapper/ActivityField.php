<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class ActivityField
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
     * Finds all field specifications associated with the $activity
     * 
     * @param \Activity\Model\Activity $activity
     * @return array of \Activity\Model\ActivityField
     */
    public function getFieldsByActivity(\Activity\Model\ActivitySignup $activity)
    {        
        return $this->getRepository()->findBy(array('activity' => $activity->getId()));
    }
    
    
    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityField');
    }
}