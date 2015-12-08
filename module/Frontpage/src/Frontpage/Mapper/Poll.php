<?php

namespace Frontpage\Mapper;

use Doctrine\ORM\EntityManager;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

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
     * Returns a poll based on its id.
     *
     * @param integer $optionId
     * @return \Frontpage\Model\PollOption|null
     */
    public function findPollOptionById($optionId)
    {
        return $this->em->find('Frontpage\Model\PollOption', $optionId);
    }

    /**
     * Find the vote of a certain user on a poll.
     *
     * @param integer $pollId
     * @param integer $lidnr
     *
     * @return \Frontpage\Model\PollVote|null
     */
    public function findVote($pollId, $lidnr)
    {
        return $this->em->getRepository('Frontpage\Model\PollVote')->findOneBy([
            'poll' => $pollId,
            'respondent' => $lidnr
        ]);
    }

    public function getUnapprovedPolls()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('p')
            ->from('Frontpage\Model\Poll', 'p')
            ->where('p.approver IS NULL')
            ->orderBy('p.expiryDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the latest poll if one is available
     *
     * @return \Frontpage\Model\Poll|null
     */
    public function getNewestPoll()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('p')
            ->from('Frontpage\Model\Poll', 'p')
            ->where('p.approver IS NOT NULL')
            ->andWhere('p.expiryDate > CURRENT_DATE()')
            ->setMaxResults(1)
            ->orderBy('p.expiryDate', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns a paginator adapter for paging through all polls.
     *
     * @return DoctrineAdapter
     */
    public function getPaginatorAdapter()
    {
        $qb = $this->getRepository()->createQueryBuilder('poll');
        $qb->where('poll.approver IS NOT NULL');
        $qb->orderBy('poll.expiryDate', 'DESC');

        return new DoctrineAdapter(new ORMPaginator($qb));
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
     * Persist.
     *
     * @param $entity an entity to persist
     */
    public function persist($entity)
    {
        $this->em->persist($entity);
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
