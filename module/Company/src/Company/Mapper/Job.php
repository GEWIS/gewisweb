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
     * Constructor
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
    /**
     * Find all jobs with the given job 'username' from the company with the given slug name.
     * @param companySlugName The slugname of the containing company.
     * @param jobSlugName The slugName of the requested job.
     * @return An array of jobs that match the request.
     */
    public function findJobsWithCompanySlugName($companySlugName)
    {

        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j','c')->join("j.company", "c")->where("c.slugName=:jobId");
        $qb->setParameter('jobId', $companySlugName);

        return $qb->getQuery()->getResult();
    }
    public function insertIntoCompany($company){
        $job=new JobModel($this->em);

        $job->setCompany($company);
        $this->em->persist($job);
//        $this->em->persist($job->getCompany());
        
//        $this->em->merge($company);
//        $this->em->merge($job);

        return $job;
    }
    public function findJobWithSlugName($companySlugName,$jobSlugName)
    {

        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join("j.company", "c") ->where("j.slugName=:jobId");
        $qb->setParameter('jobId', $companySlugName+'_'+$jobSlugName);

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
