<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\PollVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PollVote>
 */
class PollVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PollVote::class,
        );
    }

    /**
     * Find the vote of a certain user on a poll.
     */
    public function findVote(
        int $pollId,
        ?int $lidnr,
    ): ?PollVote {
        return $this->findOneBy(
            [
                'poll' => $pollId,
                'respondent' => $lidnr,
            ],
        );
    }

    /**
     * Get all poll votes cast by a specific member.
     *
     * @return PollVote[]
     */
    public function findVotesByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('v');
        $qb->where('v.respondent = :member')
            ->orderBy(
                'v.poll',
                'DESC',
            )
            ->setParameter(
                'member',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }
}
