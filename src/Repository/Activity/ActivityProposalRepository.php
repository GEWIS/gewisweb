<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\ProposalStatus;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityProposal>
 */
class ActivityProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityProposal::class,
        );
    }

    /**
     * How many of a body's proposals count against its allowance in a period.
     *
     * A proposal being edited is left out, or a body on its last slot could never save a change to the proposal that
     * used it.
     */
    public function countForPeriodAndOrgan(
        OptionPeriod $period,
        Organ $organ,
        ?ActivityProposal $excluding = null,
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.period = :period')
            ->andWhere('p.organ = :organ')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter(
                'period',
                $period,
            )
            ->setParameter(
                'organ',
                $organ,
            )
            ->setParameter(
                'statuses',
                ProposalStatus::countingTowardsAllowance(),
            );

        $this->excludeProposal(
            $qb,
            $excluding,
        );

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * The same count for a set of bodies in one go, keyed by body. Bodies with nothing handed in are absent, which the
     * caller reads as zero.
     *
     * @param Organ[] $organs
     *
     * @return array<int, int>
     */
    public function countPerOrganForPeriod(
        OptionPeriod $period,
        array $organs,
    ): array {
        if ([] === $organs) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select(
                'IDENTITY(p.organ) AS organId',
                'COUNT(p.id) AS total',
            )
            ->where('p.period = :period')
            ->andWhere('p.organ IN (:organs)')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter(
                'period',
                $period,
            )
            ->setParameter(
                'organs',
                $organs,
            )
            ->setParameter(
                'statuses',
                ProposalStatus::countingTowardsAllowance(),
            )
            ->groupBy('p.organ')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['organId']] = $row['total'];
        }

        return $counts;
    }

    /**
     * The board's queue: everything still waiting for a decision, oldest first, because who asked first is the first
     * thing the board wants to see.
     *
     * @return ActivityProposal[]
     */
    public function findAwaitingDecision(?OptionPeriod $period = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                'p',
                'o',
                'd',
            )
            ->leftJoin(
                'p.organ',
                'o',
            )
            ->leftJoin(
                'p.dateOptions',
                'd',
            )
            ->where('p.status = :status')
            ->setParameter(
                'status',
                ProposalStatus::Submitted,
            )
            ->orderBy(
                'p.createdAt',
                'ASC',
            );

        if (null !== $period) {
            $qb->andWhere('p.period = :period')
                ->setParameter(
                    'period',
                    $period,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Everything a body has going in a period, whatever became of it.
     *
     * @param Organ[] $organs
     *
     * @return ActivityProposal[]
     */
    public function findForOrgansInPeriod(
        OptionPeriod $period,
        array $organs,
    ): array {
        if ([] === $organs) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->select(
                'p',
                'd',
            )
            ->leftJoin(
                'p.dateOptions',
                'd',
            )
            ->where('p.period = :period')
            ->andWhere('p.organ IN (:organs)')
            ->setParameter(
                'period',
                $period,
            )
            ->setParameter(
                'organs',
                $organs,
            )
            ->orderBy(
                'p.createdAt',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Proposals holding a date that is coming up without the financial side having been settled, and that have not
     * been warned about it yet.
     *
     * @return ActivityProposal[]
     */
    public function findNeedingBudgetReminder(DateTime $notStartingAfter): array
    {
        return $this->uncleared($notStartingAfter)
            ->andWhere('p.budgetRemindedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Proposals whose reserved date is now so close that the option has run out of road.
     *
     * @return ActivityProposal[]
     */
    public function findDueToLapse(DateTime $notStartingAfter): array
    {
        return $this->uncleared($notStartingAfter)
            ->getQuery()
            ->getResult();
    }

    /**
     * The ids of every body that has ever proposed something, for the calendar's body filter.
     *
     * @return int[]
     */
    public function findProposingOrganIds(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT IDENTITY(p.organ) AS organId')
            ->where('p.organ IS NOT NULL')
            ->getQuery()
            ->getResult();

        $organIds = [];
        foreach ($rows as $row) {
            $organIds[] = (int) $row['organId'];
        }

        return $organIds;
    }

    /**
     * Scheduled, nothing recorded about the budget, and the reserved date starts on or before the given day.
     */
    private function uncleared(DateTime $notStartingAfter): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->select(
                'p',
                'c',
            )
            ->join(
                'p.chosenOption',
                'c',
            )
            ->where('p.status = :status')
            ->andWhere('p.budgetClearance IS NULL')
            ->andWhere('c.beginsAt <= :cutoff')
            ->setParameter(
                'status',
                ProposalStatus::Scheduled,
            )
            ->setParameter(
                'cutoff',
                $notStartingAfter,
                Types::DATE_MUTABLE,
            )
            ->orderBy(
                'c.beginsAt',
                'ASC',
            );
    }

    private function excludeProposal(
        QueryBuilder $qb,
        ?ActivityProposal $excluding,
    ): void {
        if (null === $excluding?->getId()) {
            return;
        }

        $qb->andWhere('p.id <> :excluded')
            ->setParameter(
                'excluded',
                $excluding->getId(),
            );
    }
}
