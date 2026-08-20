<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\OptionPeriod;
use App\Entity\Activity\PeriodProposalLimit;
use App\Entity\Decision\Organ;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PeriodProposalLimit>
 */
class PeriodProposalLimitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PeriodProposalLimit::class,
        );
    }

    public function findForPeriodAndOrgan(
        OptionPeriod $period,
        Organ $organ,
    ): ?PeriodProposalLimit {
        return $this->findOneBy([
            'period' => $period,
            'organ' => $organ,
        ]);
    }

    /**
     * The overrides in one period for a set of bodies, keyed by body.
     *
     * @param Organ[] $organs
     *
     * @return array<int, PeriodProposalLimit>
     */
    public function findForPeriodAndOrgans(
        OptionPeriod $period,
        array $organs,
    ): array {
        if ([] === $organs) {
            return [];
        }

        $limits = $this->createQueryBuilder('l')
            ->where('l.period = :period')
            ->andWhere('l.organ IN (:organs)')
            ->setParameter(
                'period',
                $period,
            )
            ->setParameter(
                'organs',
                $organs,
            )
            ->getQuery()
            ->getResult();

        $byOrgan = [];
        foreach ($limits as $limit) {
            $organId = $limit->getOrgan()->getId();

            if (null === $organId) {
                continue;
            }

            $byOrgan[$organId] = $limit;
        }

        return $byOrgan;
    }

    /**
     * @return PeriodProposalLimit[]
     */
    public function findForPeriod(OptionPeriod $period): array
    {
        return $this->createQueryBuilder('l')
            ->select(
                'l',
                'o',
            )
            ->join(
                'l.organ',
                'o',
            )
            ->where('l.period = :period')
            ->setParameter(
                'period',
                $period,
            )
            ->orderBy(
                'o.abbr',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
