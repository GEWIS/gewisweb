<?php

namespace Photo\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Mappers for Hit.
 *
 */
class Hit
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Get the amount of hits of all photos that have been visited 
     * in the specified time range
     * 
     * @param \DateTime $begindate
     * @param \DateTime $enddate
     * @return array of array of string
     */
    public function getHitsInRange($begindate, $enddate)
    {
        $qb = $this->em->createQueryBuilder();
        
        $qb->select('IDENTITY(hit.photo)', 'Count(hit.photo)')
           ->from('Photo\Model\Hit', 'hit')
           ->where('hit.dateTime BETWEEN ?1 AND ?2')
           ->groupBy('hit.photo')
           ->setParameter(1, $begindate)
           ->setParameter(2, $enddate);
        
        return $qb->getQuery()->getResult();
    
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
        return $this->em->getRepository('Photo\Model\Hit');
    }

}
