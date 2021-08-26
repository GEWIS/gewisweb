<?php

namespace Activity\Mapper;

use Activity\Model\ActivityCalendarOption as ActivityCalendarOptionModel;
use Activity\Model\ActivityOptionOption;
use Activity\Model\ActivityOptionProposal as ActivityOptionProposalModel;
use Application\Mapper\BaseMapper;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;

class ActivityCalendarOption extends BaseMapper
{
    /**
     * Find an option by its id.
     *
     * @param int $optionId Option id
     *
     * @return ActivityCalendarOptionModel
     */
    public function findOption($optionId)
    {
        return $this->getRepository()->findOneBy(['id' => $optionId]);
    }

    /**
     * Gets all options created by the given organs.
     *
     * @param array $organs
     * @return array
     */
    public function getUpcomingOptionsByOrgans($organs)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->from(ActivityOptionProposalModel::class, 'b')
            ->where('a.proposal = b.id')
            ->andWhere('a.endTime > :now')
            ->andWhere('b.organ IN (:organs)')
            ->orderBy('a.beginTime', 'ASC');

        $qb->setParameter('now', new DateTime())
            ->setParameter('organs', $organs);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by begin date.
     *
     * @param bool $withDeleted whether to include deleted results
     *
     * @return array
     *
     * @throws Exception
     */
    public function getUpcomingOptions($withDeleted = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->where('a.endTime > :now')
            ->orderBy('a.beginTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere('a.modifiedBy IS NULL')
                ->orWhere("a.status = 'approved'");
        }
        $qb->setParameter('now', new DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activity options sorted by begin date.
     *
     * @param DateTime $before the date to get the options before
     * @param bool $withDeleted Whether to include deleted options
     *
     * @return array
     */
    public function getPastOptions($before, $withDeleted = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->from(ActivityOptionProposalModel::class, 'b')
            ->where('a.proposal = b.id')
            ->andWhere('a.beginTime > :now')
            ->andWhere('b.creationTime < :before')
            ->orderBy('b.creationTime', 'ASC');

        if (!$withDeleted) {
            $qb->andWhere('a.modifiedBy IS NULL')
                ->orWhere("a.status = 'approved'");
        }

        $qb->setParameter('now', new DateTime());
        $qb->setParameter('before', $before);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves options associated with a proposal.
     *
     * @param int $proposalId
     * @return array
     */
    public function findOptionsByProposal($proposalId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->andWhere('a.proposal = :proposal')
            ->setParameter('proposal', $proposalId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves options associated with a proposal and associated with given organ.
     *
     * @param int $proposalId
     * @param int $organId the organ proposals have to be associated with
     *
     * @return array
     */
    public function findOptionsByProposalAndOrgan($proposalId, $organId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->andWhere('a.proposal = :proposal')
            ->setParameter('proposal', $proposalId)
            ->setParameter('organ', $organId);

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
