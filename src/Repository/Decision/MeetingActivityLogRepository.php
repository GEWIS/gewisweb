<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeetingActivityLog>
 */
class MeetingActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MeetingActivityLog::class,
        );
    }

    /**
     * @return list<MeetingActivityLog>
     */
    public function findRecentForMeeting(
        Meeting $meeting,
        int $limit = 10,
    ): array {
        $qb = $this->createRecentQueryBuilder($limit);
        $qb->join(
            'log.meeting',
            'm',
        )
            ->where('m.type = :type')
            ->andWhere('m.number = :number');

        $qb->setParameter(
            ':type',
            $meeting->getType(),
        );
        $qb->setParameter(
            ':number',
            $meeting->getNumber(),
        );

        /** @var list<MeetingActivityLog> $entries */
        $entries = $qb->getQuery()->getResult();

        return $entries;
    }

    /**
     * Recent entries of the reference library, which are not tied to a meeting.
     *
     * @return list<MeetingActivityLog>
     */
    public function findRecentForLibrary(int $limit = 10): array
    {
        // A composite-key association does not support `IS NULL`; name one of its columns instead.
        $qb = $this->createRecentQueryBuilder($limit);
        $qb->where("IDENTITY(log.meeting, 'type') IS NULL");

        /** @var list<MeetingActivityLog> $entries */
        $entries = $qb->getQuery()->getResult();

        return $entries;
    }

    private function createRecentQueryBuilder(int $limit): QueryBuilder
    {
        return $this->createQueryBuilder('log')
            ->select(
                'log',
                'actor',
            )
            ->leftJoin(
                'log.actor',
                'actor',
            )
            ->orderBy(
                'log.createdAt',
                'DESC',
            )
            ->addOrderBy(
                'log.id',
                'DESC',
            )
            ->setMaxResults($limit);
    }
}
