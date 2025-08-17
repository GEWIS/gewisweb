<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ActivityCalendarOption;
use Activity\Model\ActivityOptionProposal as ActivityOptionProposalModel;
use Application\Mapper\BaseMapper;
use DateTime;
use Override;

/**
 * @template-extends BaseMapper<ActivityOptionProposalModel>
 */
class ActivityOptionProposal extends BaseMapper
{
    /**
     * Get activity proposals within a given period and associated with given organ.
     *
     * @param DateTime $begin   the date to get the options after
     * @param DateTime $end     the date to get the options before
     * @param int      $organId the organ options have to be associated with
     *
     * @return ActivityOptionProposalModel[]
     */
    public function getNonClosedProposalsWithinPeriodAndOrgan(
        DateTime $begin,
        DateTime $end,
        int $organId,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('b');
        $qb->from(ActivityCalendarOption::class, 'a')
            ->where('a.modifiedBy IS NULL')
            ->orWhere("a.status = 'approved'")
            ->andWhere('a.proposal = b.id')
            ->andWhere('a.beginTime > :begin')
            ->setParameter('begin', $begin)
            ->andWhere('a.beginTime < :end')
            ->setParameter('end', $end)
            ->andWhere('b.organ = :organ')
            ->setParameter('organ', $organId);

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return ActivityOptionProposalModel::class;
    }
}
