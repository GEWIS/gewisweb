<?php

namespace Photo\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for Vote.
 *
 */
class Vote
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
     * Get the amount of votes of all photos that have been visited
     * in the specified time range
     * 
     * @param \DateTime $startDate
     * @param \DateTime $enddate
     * @return array of array of string
     */
    public function getVotesInRange($startDate, $endDate)
    {
        $qb = $this->em->createQueryBuilder();
        
        $qb->select('IDENTITY(vote.photo)', 'Count(vote.photo)')
           ->from('Photo\Model\Vote', 'vote')
           ->where('vote.dateTime BETWEEN ?1 AND ?2')
           ->groupBy('vote.photo')
           ->setParameter(1, $startDate)
           ->setParameter(2, $endDate);
        
        return $qb->getQuery()->getResult();
    }


    /**
     * Check if a vote exists
     * @param int $photoId The photo
     * @param int $lidnr The tag
     * @return null|object
     */
    public function findVote($photoId, $lidnr)
    {
        return $this->getRepository()->findOneBy([
            'photo' => $photoId,
            'member' => $lidnr
        ]);
    }
            
    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    public function persist($vote)
    {
        $this->em->persist($vote);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\Vote');
    }

}
