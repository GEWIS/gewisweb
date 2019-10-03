<?php

namespace Activity\Mapper;

use Activity\Model\ActivitySignup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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
     * @param ActivitySignup $signup
     * @return array of \Activity\Model\ActivityFieldValue
     */
    public function getFieldValuesBySignup(ActivitySignup $signup)
    {        
        return $this->getRepository()->findBy(array('signup' => $signup->getId()));
    }
    
    
    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\ActivityFieldValue');
    }
}
