<?php

namespace Company\Mapper;

use Company\Model\JobLabelAssignment as LabelAssignmentModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for labels assignments
 *
 */
class LabelAssignment
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function persist($labelAssignment)
    {
        $this->em->persist($labelAssignment);
        $this->em->flush();
    }

    /**
     * Saves all label assignments
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Deletes the given label assignment
     *
     * @param LabelAssignmentModel $labelAssignment
     */
    public function delete($labelAssignment)
    {
        $this->em->remove($labelAssignment);
        $this->em->flush();
    }

    /**
     * @param int $jobId
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
     * Find all Labels assignments
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\JobLabelAssignment');
    }
}
