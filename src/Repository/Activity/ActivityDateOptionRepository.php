<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\Enums\DateOptionStatus;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityDateOption>
 */
class ActivityDateOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityDateOption::class,
        );
    }

    /**
     * Every option that takes up any day in the given stretch, whether it starts before it, ends after it, or swallows
     * it whole. A month grid needs all four cases or an option running across the turn of the month vanishes from both
     * months.
     *
     * Ordered by when the proposal was handed in, so first dibs is simply the order they come back in.
     *
     * @return ActivityDateOption[]
     */
    public function findOverlapping(
        DateTime $from,
        DateTime $until,
        ?Organ $organ = null,
    ): array {
        return $this->standing($organ)
            ->andWhere('o.beginsAt <= :until')
            ->andWhere('o.endsAt >= :from')
            ->setParameter(
                'from',
                $from,
                Types::DATE_MUTABLE,
            )
            ->setParameter(
                'until',
                $until,
                Types::DATE_MUTABLE,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Who is in line for one particular day, in the order they asked.
     *
     * @return ActivityDateOption[]
     */
    public function findStandingOn(DateTime $day): array
    {
        return $this->standing()
            ->andWhere('o.beginsAt <= :day')
            ->andWhere('o.endsAt >= :day')
            ->setParameter(
                'day',
                $day,
                Types::DATE_MUTABLE,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Options that still stand, with their proposal and body fetch-joined; a calendar cell reads all three.
     */
    private function standing(?Organ $organ = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->select(
                'o',
                'p',
                'g',
            )
            ->join(
                'o.proposal',
                'p',
            )
            ->leftJoin(
                'p.organ',
                'g',
            )
            ->where('o.status IN (:statuses)')
            ->setParameter(
                'statuses',
                [
                    DateOptionStatus::Proposed,
                    DateOptionStatus::Approved,
                ],
            )
            ->orderBy(
                'p.createdAt',
                'ASC',
            )
            ->addOrderBy(
                'o.position',
                'ASC',
            );

        if (null !== $organ) {
            $qb->andWhere('p.organ = :organ')
                ->setParameter(
                    'organ',
                    $organ,
                );
        }

        return $qb;
    }
}
