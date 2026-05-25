<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\Vote;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function count;

/**
 * @extends ServiceEntityRepository<Vote>
 */
class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Vote::class,
        );
    }

    /**
     * Get the amount of votes of all photos that have been visited
     * in the specified time range.
     *
     * @return array<array-key, array{1: int, 2: int}>
     */
    public function getVotesInRange(
        DateTime $startDate,
        DateTime $endDate,
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(
            'IDENTITY(vote.photo)',
            'COUNT(vote.photo)',
        )
            ->from(
                Vote::class,
                'vote',
            )
            ->where('vote.dateTime BETWEEN :start AND :end')
            ->groupBy('vote.photo')
            ->setParameter(
                'start',
                $startDate,
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'end',
                $endDate,
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Check if a vote exists.
     *
     * @param int $photoId The photo
     * @param int $lidnr   The tag
     */
    public function findVote(
        int $photoId,
        int $lidnr,
    ): ?Vote {
        return $this->findOneBy(
            [
                'photo' => $photoId,
                'voter' => $lidnr,
            ],
        );
    }

    /**
     * Checks if a member has recently voted.
     */
    public function hasRecentVote(int $lidnr): bool
    {
        $nowMinusMonth = new DateTime('now')->sub(new DateInterval('P1M'));

        $qb = $this->createQueryBuilder('v');
        $qb->select('v.id')
            ->where('v.voter = :lidnr')
            ->andWhere('v.dateTime > :after')
            ->setParameter(
                'lidnr',
                $lidnr,
            )
            ->setParameter(
                'after',
                $nowMinusMonth,
                Types::DATETIME_MUTABLE,
            )
            ->setMaxResults(1);

        return 0 !== count($qb->getQuery()->getResult());
    }

    /**
     * @return Vote[]
     */
    public function getVotesByLidnr(int $lidnr): array
    {
        return $this->findBy(
            [
                'voter' => $lidnr,
            ],
        );
    }
}
