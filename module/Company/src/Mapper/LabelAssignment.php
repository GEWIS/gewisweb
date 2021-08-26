<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobLabelAssignment;
use Company\Model\JobLabelAssignment as LabelAssignmentModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for labels assignments.
 */
class LabelAssignment extends BaseMapper
{
    /**
     * @param int $jobId
     *
     * @return mixed
     */
    public function findAssignmentsByJobId($jobId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.job=:jobId');
        $qb->setParameter('jobId', $jobId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $jobId
     * @param int $labelId
     *
     * @return mixed
     */
    public function findAssignmentByJobIdAndLabelId($jobId, $labelId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.job=:jobId')
            ->andWhere('a.label=:labelId');
        $qb->setParameter('jobId', $jobId);
        $qb->setParameter('labelId', $labelId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobLabelAssignment::class;
    }
}
