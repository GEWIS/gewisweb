<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function intval;

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
     * How many votes each answer of a poll holds, keyed by answer id, counted in one query. Answers nobody picked are
     * absent. This is what the tallies are built from before the votes themselves go.
     *
     * @return array<int, int>
     */
    public function countsForPoll(Poll $poll): array
    {
        $counts = [];

        $rows = $this->createQueryBuilder('v')
            ->select(
                'IDENTITY(v.pollOption) AS optionId',
                'COUNT(v.respondent) AS total',
            )
            ->where('v.poll = :poll')
            ->setParameter(
                'poll',
                $poll->getId(),
            )
            ->groupBy('v.pollOption')
            ->getQuery()
            ->getScalarResult();

        foreach ($rows as $row) {
            $counts[intval($row['optionId'])] = intval($row['total']);
        }

        return $counts;
    }

    /**
     * Removes who answered a poll in a single statement, once the answers have been counted into the tallies. The
     * votes are gone from the database rather than from the entity manager, so anything holding one is out of date
     * afterwards.
     */
    public function deleteForPoll(Poll $poll): int
    {
        return intval($this->createQueryBuilder('v')
            ->delete()
            ->where('v.poll = :poll')
            ->setParameter(
                'poll',
                $poll->getId(),
            )
            ->getQuery()
            ->execute());
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
