<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityCalendarOption;
use App\Entity\Activity\ActivityOptionProposal;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityOptionProposal>
 */
class ActivityOptionProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityOptionProposal::class,
        );
    }

    /**
     * Get activity proposals within a given period and associated with given organ.
     *
     * @param DateTime $begin   the date to get the options after
     * @param DateTime $end     the date to get the options before
     * @param int      $organId the organ options have to be associated with
     *
     * @return ActivityOptionProposal[]
     */
    public function getNonClosedProposalsWithinPeriodAndOrgan(
        DateTime $begin,
        DateTime $end,
        int $organId,
    ): array {
        $qb = $this->createQueryBuilder('b');
        $qb->from(
            ActivityCalendarOption::class,
            'a',
        )
            ->where('a.modifiedBy IS NULL')
            ->orWhere("a.status = 'approved'")
            ->andWhere('a.proposal = b.id')
            ->andWhere('a.beginTime > :begin')
            ->setParameter(
                'begin',
                $begin,
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('a.beginTime < :end')
            ->setParameter(
                'end',
                $end,
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('b.organ = :organ')
            ->setParameter(
                'organ',
                $organId,
            );

        return $qb->getQuery()->getResult();
    }
}
