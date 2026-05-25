<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityCalendarOption;
use App\Entity\Activity\ActivityOptionProposal;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @extends ServiceEntityRepository<ActivityCalendarOption>
 */
class ActivityCalendarOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityCalendarOption::class,
        );
    }

    /**
     * Gets all options created by the given organs.
     *
     * @param Organ[] $organs
     *
     * @return ActivityCalendarOption[]
     */
    public function getUpcomingOptionsByOrgans(array $organs): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->from(
            ActivityOptionProposal::class,
            'b',
        )
            ->where('o.proposal = b.id')
            ->andWhere('o.endTime > :now')
            ->andWhere('b.organ IN (:organs)')
            ->orderBy(
                'o.beginTime',
                'ASC',
            );

        $qb->setParameter(
            'now',
            new DateTime(),
            Types::DATETIME_MUTABLE,
        )
            ->setParameter(
                'organs',
                $organs,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by begin date.
     *
     * @param bool $withDeleted whether to include deleted results
     *
     * @return ActivityCalendarOption[]
     *
     * @throws Exception
     */
    public function getUpcomingOptions(bool $withDeleted = false): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.endTime > :now')
            ->orderBy(
                'o.beginTime',
                'ASC',
            );

        if (!$withDeleted) {
            $qb->andWhere('o.modifiedBy IS NULL')
                ->orWhere("o.status = 'approved'");
        }

        $qb->setParameter(
            'now',
            new DateTime(),
            Types::DATETIME_MUTABLE,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get overdue options, which are created before `$before`, start after now, and are not yet modified.
     *
     * @param DateTime $before the date to get the options before
     *
     * @return ActivityCalendarOption[]
     */
    public function getOverdueOptions(DateTime $before): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->leftJoin(
            'o.proposal',
            'p',
        )
            ->andWhere('o.beginTime > :now')
            ->andWhere('p.creationTime < :before')
            ->andWhere('o.modifiedBy IS NULL')
            ->orderBy(
                'p.creationTime',
                'ASC',
            );

        $qb->setParameter(
            'now',
            new DateTime(),
            Types::DATETIME_MUTABLE,
        );
        $qb->setParameter(
            'before',
            $before,
            Types::DATETIME_MUTABLE,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves options associated with a proposal.
     *
     * @return ActivityCalendarOption[]
     */
    public function findOptionsByProposal(ActivityOptionProposal $proposal): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.proposal = :proposal')
            ->setParameter(
                'proposal',
                $proposal,
                ActivityOptionProposal::class,
            );

        return $qb->getQuery()->getResult();
    }
}
