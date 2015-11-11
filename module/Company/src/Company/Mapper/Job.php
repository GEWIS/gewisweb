<?php

namespace Company\Mapper;

use Company\Model\Job as JobModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for jobs.
 *
 * NOTE: Jobs will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Job
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

    /**
     * Find all companies.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    public function save()
    {
        $this->em->flush();
    }
    /**
     * Find all jobs with the given job 'username' from the company with the given slug name.
     *
     * @param companySlugName The slugname of the containing company.
     * @param jobSlugName The slugName of the requested job.
     *
     * @return An array of jobs that match the request.
     */
    public function findJobsWithCompanySlugName($packetID)
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join('j.packet', 'p')->join('p.company', 'c')->where('p.id=:companyId')
            ->andWhere('j.active=1')->andWhere('c.hidden=0')->andWhere('p.expires > CURRENT_DATE()');
        $qb->setParameter('companyId', $packetID);

        return $qb->getQuery()->getResult();
    }

    public function insertIntoPacket($packet)
    {
        $job = new JobModel($this->em);

        $job->setPacket($packet);
        $this->em->persist($job);

        return $job;
    }

    public function findJobWithSlugName($companySlugName, $jobSlugName)
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join('j.packet', 'p')->join('p.company', 'c')->where('j.slugName=:jobId')
        ->andWhere('c.slugName=:companySlugName');
        $qb->setParameter('jobId', $jobSlugName);
        $qb->setParameter('companySlugName', $companySlugName);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\Job');
    }
}
