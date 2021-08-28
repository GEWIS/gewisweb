<?php

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Model\PollOption;
use Frontpage\Model\PollVote;

/**
 * Mappers for Polls.
 */
class Poll extends BaseMapper
{
    /**
     * Returns a poll based on its id.
     *
     * @param int $optionId
     *
     * @return PollOption|null
     */
    public function findPollOptionById($optionId)
    {
        return $this->em->find(PollOption::class, $optionId);
    }

    /**
     * Find the vote of a certain user on a poll.
     *
     * @param int $pollId
     * @param int $lidnr
     *
     * @return PollVote|null
     */
    public function findVote($pollId, $lidnr)
    {
        return $this->em->getRepository(PollVote::class)->findOneBy(
            [
                'poll' => $pollId,
                'respondent' => $lidnr,
            ]
        );
    }

    public function getUnapprovedPolls()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('p')
            ->from($this->getRepositoryName(), 'p')
            ->where('p.approver IS NULL')
            ->orderBy('p.expiryDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the latest poll if one is available.
     *
     * @return PollModel|null
     */
    public function getNewestPoll()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('p')
            ->from($this->getRepositoryName(), 'p')
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

    protected function getRepositoryName(): string
    {
        return PollModel::class;
    }
}
