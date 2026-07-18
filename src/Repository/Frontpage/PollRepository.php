<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Poll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Poll>
 */
class PollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Poll::class,
        );
    }

    /**
     * @return Poll[]
     */
    public function getUnapprovedPolls(): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.approver IS NULL')
            ->orderBy(
                'p.expiryDate',
                'DESC',
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the latest poll if one is available. Please note that this returns the poll which has its expiryDate
     * furthest into the future, and thus not necessarily the 'newest' poll.
     */
    public function getNewestPoll(): ?Poll
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.approver IS NOT NULL')
            ->andWhere('p.expiryDate > CURRENT_DATE()')
            ->setMaxResults(1)
            ->orderBy(
                'p.expiryDate',
                'DESC',
            );

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }

    /**
     * Returns a paginator adapter for paging through all polls.
     *
     * @return Paginator<Poll>
     */
    public function getPaginatorAdapter(
        int $page = 1,
        int $limit = 15,
    ): Paginator {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.approver IS NOT NULL')
            ->orderBy(
                'p.expiryDate',
                'DESC',
            );

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * Get all polls created by a specific member.
     *
     * @return Poll[]
     */
    public function findPollsCreatedByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.creator = :member')
            ->setParameter(
                'member',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all polls approved by a specific member.
     *
     * @return Poll[]
     */
    public function findPollsApprovedByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.approver = :member')
            ->setParameter(
                'member',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }
}
