<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\OptionPeriod;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OptionPeriod>
 */
class OptionPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OptionPeriod::class,
        );
    }

    /**
     * The periods bodies may hand proposals in for right now, soonest first. More than one can be open at a time when
     * the board runs ahead, and each is counted on its own.
     *
     * @return OptionPeriod[]
     */
    public function findOpenAt(DateTime $moment): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.submissionOpensAt <= :moment')
            ->andWhere('p.submissionClosesAt >= :moment')
            ->setParameter(
                'moment',
                $moment,
                Types::DATETIME_MUTABLE,
            )
            ->orderBy(
                'p.startsAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Everything that has not finished yet, for the board's overview.
     *
     * @return OptionPeriod[]
     */
    public function findCurrentAndUpcoming(DateTime $moment): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.endsAt >= :today')
            ->setParameter(
                'today',
                $moment,
                Types::DATE_MUTABLE,
            )
            ->orderBy(
                'p.startsAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OptionPeriod[]
     */
    public function findAllNewestFirst(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy(
                'p.startsAt',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }
}
