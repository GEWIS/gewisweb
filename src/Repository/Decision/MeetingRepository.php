<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingMinutesVersion;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function array_merge;
use function array_reverse;
use function array_slice;
use function count;
use function is_int;
use function min;
use function sprintf;

/**
 * @extends ServiceEntityRepository<Meeting>
 * @phpstan-type MeetingArrayType = array<array-key, array{
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
                    $type->value,
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
                $type->value,
            );
        $this->selectOneToOneSides($qb);

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
                $type->value,
            )
            ->setMaxResults($limit);
        $this->selectOneToOneSides($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Selects the meeting's one-to-one sides in the same query. Inverse one-to-one associations cannot be lazy
     * proxies, so without this every hydrated meeting costs three extra queries.
     */
    private function selectOneToOneSides(QueryBuilder $qb): void
    {
        $qb->addSelect('meetingMinutes, localDetails, decisionMinutes')
            ->leftJoin(
                'm.meetingMinutes',
                'meetingMinutes',
            )
            ->leftJoin(
                'm.localDetails',
                'localDetails',
            )
            ->leftJoin(
                'm.minutes',
                'decisionMinutes',
            );
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
        $this->selectOneToOneSides($qb);

        $qb->setParameter(
            ':type',
            $type->value,
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
     * All upcoming ALVs, soonest first.
     *
     * @return Meeting[]
     */
    public function findUpcomingALVs(): array
    {
        $qb = $this->createQueryBuilder('m');

        $today = new DateTime();
        $maxDate = $today->sub(new DateInterval('P1D'));

        $qb->where('m.type = :gmm')
            ->andWhere('m.date >= :date')
            ->orderBy(
                'm.date',
                'ASC',
            )
            ->setParameter(
                'gmm',
                MeetingTypes::ALV,
            )
            ->setParameter(
                'date',
                $maxDate,
                Types::DATETIME_MUTABLE,
            );
        $this->selectOneToOneSides($qb);

        return $qb->getQuery()->getResult();
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
        $this->selectOneToOneSides($qb);

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

    /**
     * One page of the meetings overview: meetings newest first with their decision count and whether minutes have
     * been uploaded, optionally narrowed to a type and/or an exact meeting number.
     *
     * @return array{items: list<array{0: Meeting, 1: int<0, max>, 2: int<0, max>}>, total: int}
     */
    public function paginateForOverview(
        ?MeetingTypes $type,
        ?int $number,
        int $page,
        int $pageSize,
        bool $excludeVirtual = false,
    ): array {
        $filter = static function (QueryBuilder $qb) use ($type, $number, $excludeVirtual): void {
            if (null !== $type) {
                $qb->andWhere('m.type = :type')
                    ->setParameter(
                        ':type',
                        $type->value,
                    );
            } elseif ($excludeVirtual) {
                $qb->andWhere('m.type != :virtual')
                    ->setParameter(
                        ':virtual',
                        MeetingTypes::VIRT->value,
                    );
            }

            if (null === $number) {
                return;
            }

            $qb->andWhere('m.number = :number')
                ->setParameter(
                    ':number',
                    $number,
                );
        };

        $countQb = $this->createQueryBuilder('m')
            ->select('COUNT(m.number)');
        $filter($countQb);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $itemsQb = $this->createQueryBuilder('m')
            ->addSelect(sprintf(
                '(SELECT COUNT(d.number) FROM %s d'
                . ' WHERE d.meeting_type = m.type AND d.meeting_number = m.number) AS decisionCount',
                Decision::class,
            ))
            ->addSelect(sprintf(
                '(SELECT COUNT(mv.id) FROM %s mv JOIN mv.minutes mm'
                . ' WHERE mm.meeting_type = m.type AND mm.meeting_number = m.number) AS minutesVersionCount',
                MeetingMinutesVersion::class,
            ))
            ->orderBy(
                'm.date',
                'DESC',
            )
            ->addOrderBy(
                'm.number',
                'DESC',
            )
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);
        $this->selectOneToOneSides($itemsQb);
        $filter($itemsQb);

        /** @var list<array{0: Meeting, decisionCount: int<0, max>, minutesVersionCount: int<0, max>}> $rows */
        $rows = $itemsQb->getQuery()->getResult();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                $row[0],
                $row['decisionCount'],
                $row['minutesVersionCount'],
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * The meetings of the same type directly before the given one.
     *
     * @return list<Meeting>
     */
    public function findPrevious(
        Meeting $meeting,
        int $limit = 3,
    ): array {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.type = :type')
            ->andWhere('m.number < :number')
            ->orderBy(
                'm.number',
                'DESC',
            )
            ->setMaxResults($limit);
        $this->selectOneToOneSides($qb);

        $qb->setParameter(
            ':type',
            $meeting->getType(),
        );
        $qb->setParameter(
            ':number',
            $meeting->getNumber(),
        );

        /** @var list<Meeting> $meetings */
        $meetings = $qb->getQuery()->getResult();

        return $meetings;
    }

    /**
     * Scalar rows for the nearby-meetings sidebar: ideally two meetings after and two before the given one (of the
     * same type), either side filling in for the other when it runs short, newest first. Deliberately not entity
     * hydration; the sidebar only links, and entities drag their one-to-one sides along.
     *
     * @return list<array{type: MeetingTypes, number: int, date: DateTime}>
     */
    public function findNearby(Meeting $meeting): array
    {
        $total = 4;
        $before = $this->nearbyQuery(
            $meeting,
            '<',
            'DESC',
            $total,
        );
        $after = $this->nearbyQuery(
            $meeting,
            '>',
            'ASC',
            $total,
        );

        $takeAfter = min(
            2,
            count($after),
        );
        $takeBefore = min(
            $total - $takeAfter,
            count($before),
        );
        $takeAfter = min(
            $total - $takeBefore,
            count($after),
        );

        return array_merge(
            array_reverse(array_slice(
                $after,
                0,
                $takeAfter,
            )),
            array_slice(
                $before,
                0,
                $takeBefore,
            ),
        );
    }

    /**
     * @return list<array{type: MeetingTypes, number: int, date: DateTime}>
     */
    private function nearbyQuery(
        Meeting $meeting,
        string $comparison,
        string $direction,
        int $limit,
    ): array {
        $qb = $this->createQueryBuilder('m');
        $qb->select('m.type, m.number, m.date')
            ->where('m.type = :type')
            ->andWhere('m.number ' . $comparison . ' :number')
            ->orderBy(
                'm.number',
                $direction,
            )
            ->setMaxResults($limit);

        $qb->setParameter(
            ':type',
            $meeting->getType()->value,
        );
        $qb->setParameter(
            ':number',
            $meeting->getNumber(),
        );

        /** @var list<array{type: MeetingTypes, number: int, date: DateTime}> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        return $rows;
    }
}
