<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Model\PollVote as PollVoteModel;
use Override;

/**
 * Mappers for Polls.
 *
 * @template-extends BaseMapper<PollModel>
 */
class Poll extends BaseMapper
{
    /**
     * Find the vote of a certain user on a poll.
     */
    public function findVote(
        int $pollId,
        ?int $lidnr,
    ): ?PollVoteModel {
        return $this->getEntityManager()->getRepository(PollVoteModel::class)->findOneBy(
            [
                'poll' => $pollId,
                'respondent' => $lidnr,
            ],
        );
    }

    /**
     * @return PollModel[]
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
     */
    public function getPaginatorAdapter(): DoctrineAdapter
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.approver IS NOT NULL')
            ->orderBy('p.expiryDate', 'DESC');

        return new DoctrineAdapter(new Paginator($qb));
    }

    /**
     * Get all poll votes casted by a specific member.
     *
     * @return PollVoteModel[]
     */
    public function findVotesByMember(MemberModel $member): array
    {
        $qb = $this->getEntityManager()->getRepository(PollVoteModel::class)->createQueryBuilder('v');
        $qb->where('v.respondent = :member')
            ->orderBy('v.poll', 'DESC')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all polls created by a specific member.
     *
     * @return PollModel[]
     */
    public function findPollsCreatedByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.creator = :member')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all polls approved by a specific member.
     *
     * @return PollModel[]
     */
    public function findPollsApprovedByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.approver = :member')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return PollModel::class;
    }
}
