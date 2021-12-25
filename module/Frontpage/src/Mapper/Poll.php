<?php

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Frontpage\Model\{
    Poll as PollModel,
    PollOption as PollOptionModel,
    PollVote as PollVoteModel,
};

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
     * @return PollOptionModel|null
     */
    public function findPollOptionById(int $optionId): ?PollOptionModel
    {
        return $this->em->getRepository(PollOptionModel::class)->find($optionId);
    }

    /**
     * Find the vote of a certain user on a poll.
     *
     * @param int $pollId
     * @param int $lidnr
     *
     * @return PollVoteModel|null
     */
    public function findVote(
        int $pollId,
        int $lidnr,
    ): ?PollVoteModel {
        return $this->em->getRepository(PollVoteModel::class)->findOneBy(
            [
                'poll' => $pollId,
                'respondent' => $lidnr,
            ]
        );
    }

    /**
     * @return array
     */
    public function getUnapprovedPolls(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.approver IS NULL')
            ->orderBy('p.expiryDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the latest poll if one is available. Please note that this returns the poll which has its expiryDate
     * furthest into the future, and thus not necessarily the 'newest' poll.
     *
     * @return PollModel|null
     */
    public function getNewestPoll(): ?PollModel
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.approver IS NOT NULL')
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
    public function getPaginatorAdapter(): DoctrineAdapter
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.approver IS NOT NULL')
            ->orderBy('p.expiryDate', 'DESC');

        return new DoctrineAdapter(new Paginator($qb));
    }

    /**
     * @inheritdoc
     */
    protected function getRepositoryName(): string
    {
        return PollModel::class;
    }
}
