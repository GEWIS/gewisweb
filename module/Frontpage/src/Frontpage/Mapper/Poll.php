<?php

namespace Frontpage\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for Polls.
 *
 */
class Poll
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
     * Returns a poll based on its id.
     *
     * @param integer $pollId
     * @return \Frontpage\Model\Poll|null
     */
    public function findPollById($pollId)
    {
        return $this->getRepository()->find($pollId);
    }

    /**
     * Returns the latest poll if one is available
     *
     * @return \Frontpage\Model\Poll|null
     */
    public function getNewestPoll() {
        $qb = $this->em->createQueryBuilder();

        $qb->select('p')
            ->from('Frontpage\Model\Poll', 'p')
            ->andWhere('p.expiryDate > CURRENT_DATE()')
            ->setMaxResults(1)
            ->orderBy('p.expiryDate', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Removes a poll.
     *
     * @param \Frontpage\Model\Poll $poll
     */
    public function remove($poll)
    {
        $this->em->remove($poll);
    }

    /**
     * Persist a poll.
     *
     * @param \Frontpage\Model\Poll $poll
     */
    public function persist($poll)
    {
        $this->em->persist($poll);
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
        return $this->em->getRepository('Frontpage\Model\Poll');
    }

}
