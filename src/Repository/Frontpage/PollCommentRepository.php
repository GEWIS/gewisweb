<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function intval;

/**
 * @extends ServiceEntityRepository<PollComment>
 */
class PollCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PollComment::class,
        );
    }

    /**
     * Loads a poll's whole discussion at once: the comments with who wrote them, each comment's reactions and each
     * comment's replies. Without this every rendered comment fetches its own three, a query apiece on a thread that
     * re-renders on every post, reply and reaction.
     */
    public function primeThread(Poll $poll): void
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->select(
                'p',
                'c',
                'u',
            )
            ->from(
                Poll::class,
                'p',
            )
            ->leftJoin(
                'p.comments',
                'c',
            )
            ->leftJoin(
                'c.user',
                'u',
            )
            ->where('p = :poll')
            ->setParameter(
                'poll',
                $poll,
            )
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('c')
            ->select(
                'c',
                'r',
            )
            ->leftJoin(
                'c.reactions',
                'r',
            )
            ->where('c.poll = :poll')
            ->setParameter(
                'poll',
                $poll,
            )
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('c')
            ->select(
                'c',
                'rep',
            )
            ->leftJoin(
                'c.replies',
                'rep',
            )
            ->where('c.poll = :poll')
            ->setParameter(
                'poll',
                $poll,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * How many top-level comments each of these polls has, keyed by poll id. The archive cards say the number and
     * nothing else, so the threads themselves stay unloaded.
     *
     * @param list<Poll> $polls
     *
     * @return array<int, int>
     */
    public function countTopLevelForPolls(array $polls): array
    {
        if ([] === $polls) {
            return [];
        }

        $rows = $this->createQueryBuilder('c')
            ->select(
                'IDENTITY(c.poll) AS poll',
                'COUNT(c.id) AS comments',
            )
            ->where('c.poll IN (:polls)')
            ->andWhere('c.parent IS NULL')
            ->groupBy('c.poll')
            ->setParameter(
                'polls',
                $polls,
            )
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[intval($row['poll'])] = intval($row['comments']);
        }

        return $counts;
    }

    /**
     * Get all poll comments made by specific member.
     *
     * @return PollComment[]
     */
    public function findByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.user = :member')
            ->orderBy(
                'c.createdOn',
                'DESC',
            )
            ->setParameter(
                'member',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }
}
