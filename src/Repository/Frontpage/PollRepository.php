<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollVote;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function intval;
use function min;
use function trim;

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
     * The question the association is being asked at the moment. Approving a poll is also scheduling it, so of the
     * questions the board has agreed to this is the one whose closing date lies furthest into the future.
     */
    public function findCurrentPoll(): ?Poll
    {
        // The limit is applied without the answers joined in: a fetch-join would make it count rows rather than
        // polls. What the one poll needs to render is loaded straight after.
        $polls = $this->createQueryBuilder('p')
            ->where('p.liveRevision IS NOT NULL')
            ->andWhere('p.expiryDate > CURRENT_DATE()')
            ->orderBy(
                'p.expiryDate',
                'DESC',
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        $this->primeResults($polls);

        return $polls[0] ?? null;
    }

    /**
     * Loads the question and its answers for a whole page of polls at once, and counts the votes on all of them in a
     * second query. Without this every answer fetches its own text and counts its own votes, which is a query apiece.
     *
     * The votes are counted rather than loaded: a well-answered poll has a row per member, and none of them is worth
     * hydrating to arrive at a number.
     *
     * @param list<Poll> $polls
     */
    public function primeResults(array $polls): void
    {
        if ([] === $polls) {
            return;
        }

        $this->createQueryBuilder('p')
            ->select(
                'p',
                'r',
                'q',
                'o',
                't',
            )
            ->join(
                'p.liveRevision',
                'r',
            )
            ->join(
                'r.question',
                'q',
            )
            ->leftJoin(
                'r.options',
                'o',
            )
            ->leftJoin(
                'o.text',
                't',
            )
            ->where('p IN (:polls)')
            ->setParameter(
                'polls',
                $polls,
            )
            ->getQuery()
            ->getResult();

        $this->primeVoteCounts($polls);
    }

    /**
     * Hands every answer of the given polls the number of votes on it, so counting one does not fall back to loading
     * the votes. Answers nobody picked are absent from the grouped query and are told they are at nothing.
     *
     * @param list<Poll> $polls
     */
    private function primeVoteCounts(array $polls): void
    {
        $counted = [];

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'IDENTITY(v.pollOption) AS optionId',
                'COUNT(v.respondent) AS total',
            )
            ->from(
                PollVote::class,
                'v',
            )
            ->where('v.poll IN (:polls)')
            ->setParameter(
                'polls',
                $polls,
            )
            ->groupBy('v.pollOption')
            ->getQuery()
            ->getScalarResult();

        foreach ($rows as $row) {
            $counted[intval($row['optionId'])] = intval($row['total']);
        }

        foreach ($polls as $poll) {
            foreach ($poll->getOptions() as $option) {
                $option->setCountedVotes($counted[intval($option->getId())] ?? 0);
            }
        }
    }

    /**
     * The most recently scheduled questions the board has agreed to, newest closing date first, results loaded: what
     * the admin overview lists. Anything older is found through the public archive.
     *
     * @return Poll[]
     */
    public function findRecentApproved(int $limit): array
    {
        $polls = $this->createQueryBuilder('p')
            ->where('p.liveRevision IS NOT NULL')
            ->orderBy(
                'p.expiryDate',
                'DESC',
            )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $this->primeResults($polls);

        return $polls;
    }

    /**
     * The archive: every question the board has ever agreed to, narrowed by what the reader typed and by whether they
     * answered it, newest first unless they asked otherwise.
     *
     * The search reaches the answers as well as the question, since a poll is often remembered by what could be
     * answered rather than by how it was asked.
     *
     * @return Paginator<Poll>
     */
    public function getArchivePaginator(
        int $page = 1,
        int $limit = 10,
        string $search = '',
        ?Member $member = null,
        ?bool $answered = null,
        bool $oldestFirst = false,
    ): Paginator {
        $builder = $this->createQueryBuilder('p')
            ->join(
                'p.liveRevision',
                'r',
            )
            ->join(
                'r.question',
                'q',
            )
            ->orderBy(
                'p.expiryDate',
                $oldestFirst
                    ? 'ASC'
                    : 'DESC',
            );

        $search = trim($search);
        if ('' !== $search) {
            $builder->leftJoin(
                'r.options',
                'o',
            )
                ->leftJoin(
                    'o.text',
                    't',
                )
                ->andWhere(
                    'q.valueEN LIKE :search OR q.valueNL LIKE :search'
                    . ' OR t.valueEN LIKE :search OR t.valueNL LIKE :search',
                )
                ->setParameter(
                    'search',
                    '%' . addcslashes(
                        $search,
                        '%_',
                    ) . '%',
                )
                ->distinct();
        }

        // Whether this reader answered it, which only means anything while they are signed in and the votes have not
        // been anonymised out from under them.
        if (
            null !== $member
            && null !== $answered
        ) {
            $exists = 'EXISTS (SELECT 1 FROM ' . PollVote::class . ' pv'
                . ' WHERE pv.poll = p AND pv.respondent = :member)';

            $builder->andWhere($answered
                ? $exists
                : 'NOT ' . $exists)
                ->setParameter(
                    'member',
                    $member->getLidnr(),
                );
        }

        $paginator = new Paginator($builder);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * A few questions from just before this one, so a poll sits among the others rather than on its own.
     *
     * @return Poll[]
     */
    public function findEarlierThan(
        Poll $poll,
        int $limit,
    ): array {
        $polls = $this->createQueryBuilder('p')
            ->where('p.liveRevision IS NOT NULL')
            ->andWhere('p.id != :poll')
            ->andWhere('p.expiryDate <= :expiryDate')
            ->setParameter(
                'poll',
                $poll->getId(),
            )
            ->setParameter(
                'expiryDate',
                $poll->getExpiryDate(),
                Types::DATE_MUTABLE,
            )
            ->orderBy(
                'p.expiryDate',
                'DESC',
            )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $this->primeResults($polls);

        return $polls;
    }

    /**
     * The polls whose votes are due to be turned into anonymous tallies: those that closed a month ago or longer and
     * have not been through it yet.
     *
     * @return Poll[]
     */
    public function findDueForVoteAnonymisation(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.expiryDate <= :cutoff')
            ->andWhere('p.votesAnonymisedAt IS NULL')
            ->setParameter(
                'cutoff',
                $this->aMonthAgo(),
                Types::DATE_MUTABLE,
            )
            ->orderBy(
                'p.expiryDate',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * A month back from today, without the overflow that "-1 month" has on the days a month can be short of: it turns
     * the 31st of March into the 3rd of March, which would take a poll's votes three days before the month is up. A
     * day the earlier month does not have becomes its last one.
     */
    private function aMonthAgo(): DateTime
    {
        $today = new DateTime('today');
        $cutoff = new DateTime('today')->modify('first day of last month');

        return $cutoff->setDate(
            intval($cutoff->format('Y')),
            intval($cutoff->format('n')),
            min(
                intval($today->format('j')),
                intval($cutoff->format('t')),
            ),
        );
    }

    /**
     * Get all polls created by a specific member.
     *
     * @return Poll[]
     */
    public function findPollsCreatedByMember(Member $member): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.creator = :member')
            ->setParameter(
                'member',
                $member->getLidnr(),
            )
            ->getQuery()
            ->getResult();
    }
}
