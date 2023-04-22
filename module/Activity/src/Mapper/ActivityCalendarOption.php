<?php

namespace Activity\Mapper;

use Activity\Model\{
    ActivityCalendarOption as ActivityCalendarOptionModel,
    ActivityOptionProposal as ActivityOptionProposalModel,
};
use Application\Mapper\BaseMapper;
use DateTime;
use Exception;

/**
 * @template-extends BaseMapper<ActivityCalendarOptionModel>
 */
class ActivityCalendarOption extends BaseMapper
{
    /**
     * Gets all options created by the given organs.
     *
     * @param array $organs
     * @return array<array-key, ActivityCalendarOptionModel>
     */
    public function getUpcomingOptionsByOrgans(array $organs): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->from(ActivityOptionProposalModel::class, 'b')
            ->where('o.proposal = b.id')
            ->andWhere('o.endTime > :now')
            ->andWhere('b.organ IN (:organs)')
            ->orderBy('o.beginTime', 'ASC');

        $qb->setParameter('now', new DateTime())
            ->setParameter('organs', $organs);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by begin date.
     *
     * @param bool $withDeleted whether to include deleted results
     *
     * @return array<array-key, ActivityCalendarOptionModel>
     *
     * @throws Exception
     */
    public function getUpcomingOptions(bool $withDeleted = false): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.endTime > :now')
            ->orderBy('o.beginTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere('o.modifiedBy IS NULL')
                ->orWhere("o.status = 'approved'");
        }
        $qb->setParameter('now', new DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get overdue options, which are created before `$before`, start after now, and are not yet modified.
     *
     * @param DateTime $before the date to get the options before
     *
     * @return array<array-key, ActivityCalendarOptionModel>
     */
    public function getOverdueOptions(DateTime $before): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->leftJoin('o.proposal', 'p')
            ->andWhere('o.beginTime > :now')
            ->andWhere('p.creationTime < :before')
            ->andWhere('o.modifiedBy IS NULL')
            ->orderBy('p.creationTime', 'ASC');

        $qb->setParameter('now', new DateTime());
        $qb->setParameter('before', $before);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves options associated with a proposal.
     *
     * @param ActivityOptionProposalModel $proposal
     * @return array<array-key, ActivityCalendarOptionModel>
     */
    public function findOptionsByProposal(ActivityOptionProposalModel $proposal): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.proposal = :proposal')
            ->setParameter('proposal', $proposal);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityCalendarOptionModel::class;
    }
}
