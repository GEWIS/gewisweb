<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

use function is_int;

/**
 * @extends ServiceEntityRepository<Meeting>
 * @psalm-type MeetingArrayType = array<array-key, array{
 *      0: Meeting,
 *      1: int,
 * }>
 */
class MeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Meeting::class,
        );
    }

    /**
     * Find all meetings.
     *
     * @param int|null $limit The amount of results, default is all
     *
     * @return MeetingArrayType
     */
    public function findAllMeetings(
        ?int $limit = null,
        ?MeetingTypes $type = null,
    ): array {
        $qb = $this->createQueryBuilder('m');
        $qb->addSelect('COUNT(d)')
            ->leftJoin(
                'm.decisions',
                'd',
            )
            ->groupBy('m')
            ->orderBy(
                'm.date',
                'DESC',
            );

        if (
            is_int($limit)
            && $limit >= 0
        ) {
            $qb->setMaxResults($limit);
        }

        if (null !== $type) {
            $qb->andWhere('m.type = :type')
                ->setParameter(
                    ':type',
                    $type,
                    MeetingTypes::class,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings which have the given type.
     *
     * @param MeetingTypes $type ALV|BV|VV|Virt
     *
     * @return Meeting[]
     */
    public function findByType(MeetingTypes $type): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.type = :type')
            ->orderBy(
                'm.date',
                'DESC',
            )
            ->setParameter(
                ':type',
                $type,
                MeetingTypes::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings that have taken place.
     *
     * @param int $limit The amount of results
     *
     * @return Meeting[] Meetings that have taken place
     */
    public function findPast(
        int $limit,
        MeetingTypes $type,
    ): array {
        // Use yesterday because a meeting might still take place later on the day
        $date = new DateTime('yesterday');

        $qb = $this->createQueryBuilder('m')
            ->where('m.date <= :date')
            ->andWhere('m.type = :type')
            ->orderBy(
                'm.date',
                'DESC',
            )
            ->setParameter(
                'date',
                $date,
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'type',
                $type,
                MeetingTypes::class,
            )
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a meeting with all decisions.
     *
     * @throws NonUniqueResultException
     */
    public function findMeeting(
        MeetingTypes $type,
        int $number,
    ): ?Meeting {
        $qb = $this->createQueryBuilder('m');
        $qb->addSelect('d, db')
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin(
                'm.decisions',
                'd',
            )
            ->leftJoin(
                'd.annulledBy',
                'db',
            )
            ->orderBy('d.point')
            ->addOrderBy('d.number');

        $qb->setParameter(
            ':type',
            $type,
            MeetingTypes::class,
        );
        $qb->setParameter(
            ':number',
            $number,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns the maximum document position for the given meeting.
     *
     * @return int|null NULL if no documents are associated to the meeting
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findMaxDocumentPosition(Meeting $meeting): ?int
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('MAX(d.displayPosition)')
            ->join(
                'm.documents',
                'd',
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

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the latest upcoming ALV or null if there is none.
     *
     * Note that if multiple ALVs are planned, the one that is planned furthest
     * away is returned.
     *
     * @throws NonUniqueResultException
     */
    public function findLatestALV(): ?Meeting
    {
        $qb = $this->createQueryBuilder('m');

        $today = new DateTime();
        $maxDate = $today->sub(new DateInterval('P1D'));

        $qb->where('m.type = :gmm')
            ->andWhere('m.date >= :date')
            ->orderBy(
                'm.date',
                'DESC',
            )
            ->setParameter(
                'gmm',
                MeetingTypes::ALV,
            )
            ->setParameter(
                'date',
                $maxDate,
                Types::DATETIME_MUTABLE,
            )
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Meeting[]
     */
    public function findUpcomingAnnouncedMeetings(): array
    {
        $qb = $this->createQueryBuilder('m');

        $today = new DateTime();
        $maxDate = $today->sub(new DateInterval('P1D'));

        $qb->where('m.type = :gmm OR m.type = :cm')
            ->andWhere('m.date >= :date')
            ->orderBy(
                'm.date',
                'ASC',
            );

        $qb->setParameter(
            'gmm',
            MeetingTypes::ALV,
        )
            ->setParameter(
                'cm',
                MeetingTypes::VV,
            )
            ->setParameter(
                'date',
                $maxDate,
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }
}
