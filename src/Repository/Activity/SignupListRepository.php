<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\DrawCutoffRule;
use App\Entity\Activity\SignupList;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignupList>
 */
class SignupListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SignupList::class,
        );
    }

    /**
     * The live-revision sign-up lists whose automated draw moment ({@see SignupList::getAutoDrawAt()}) has passed but
     * that have not been drawn yet, still inside the admission window (until a day after the activity ends, mirroring
     * {@see \App\Service\Activity\SignupAdminWindow::canChangeAdmission()}). A coarse SQL pre-filter only:
     * {@see \App\Service\Activity\DrawManager::drawAutomatically()} re-checks every guard under a row lock.
     *
     * @return SignupList[]
     */
    public function findDueForAutomatedDraw(DateTime $now): array
    {
        return $this->createQueryBuilder('sl')
            ->innerJoin(
                'sl.revision',
                'r',
            )
            ->innerJoin(
                'r.activity',
                'a',
                Join::WITH,
                'a.liveRevision = r',
            )
            ->where('sl.drawnAt IS NULL')
            ->andWhere('sl.limitedCapacity = true')
            ->andWhere('sl.capacity >= 1')
            ->andWhere('sl.openDate <= :now')
            ->andWhere('r.endTime IS NOT NULL')
            ->andWhere('r.endTime >= :admissionBound')
            ->andWhere(
                '(sl.allocationMethod = :fcfs AND sl.closeDate <= :now)'
                . ' OR (sl.allocationMethod = :conditionalDraw AND ('
                . '(sl.drawCutoffRule = :onClose AND sl.closeDate <= :now)'
                . ' OR (sl.drawCutoffRule = :ifFullBefore AND sl.drawCutoffAt IS NOT NULL'
                . ' AND sl.drawCutoffAt <= :now)'
                . ' OR (sl.drawCutoffRule = :afterDuration AND sl.drawAfterDurationHours IS NOT NULL'
                . " AND DATE_ADD(sl.openDate, sl.drawAfterDurationHours, 'HOUR') <= :now)"
                . '))',
            )
            ->setParameter(
                'now',
                $now,
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'admissionBound',
                (clone $now)->modify('-1 day'),
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'fcfs',
                AllocationMethod::FirstComeFirstServed,
            )
            ->setParameter(
                'conditionalDraw',
                AllocationMethod::ConditionalDraw,
            )
            ->setParameter(
                'onClose',
                DrawCutoffRule::OnClose,
            )
            ->setParameter(
                'ifFullBefore',
                DrawCutoffRule::IfFullBefore,
            )
            ->setParameter(
                'afterDuration',
                DrawCutoffRule::AfterDurationOpen,
            )
            ->getQuery()
            ->getResult();
    }
}
