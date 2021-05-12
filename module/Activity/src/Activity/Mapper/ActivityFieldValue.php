<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class ActivityFieldValue
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
     * Finds all field values associated with the $signup
     * 
     * @param \Activity\Model\ActivitySignup $signup
     * @return array of \Activity\Model\ActivityFieldValue
     */
    public function getFieldValuesBySignup(\Activity\Model\ActivitySignup $signup)
    {        
        return $this->getRepository()->findBy(array('signup' => $signup->getId()));
    }
    
    
    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityFieldValue');
    }
}