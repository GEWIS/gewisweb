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
     * Find all jobs with the given job 'username' from the company with the given ascii name.
     * @param companyAsciiName The asciiname of the containing company.
     * @param jobAsciiName The asciiName of the requested job.
     * @return An array of jobs that match the request.
     */
    public function findJobsWithCompanyAsciiName($companyAsciiName)
    {

        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->from("Company\Model\Company", "c")->where("j.company = c AND c.asciiName = :jobID");
        $qb->setParameter('jobID', $companyAsciiName);

        $return =  $qb->getQuery()->getResult();
        return $return;
    }
    public function insertIntoCompany($company){
        $job=new JobModel($this->em);

        $job->setCompany($company);
        $company->getJobs()->add($job);
        $this->em->persist($job);
        //$this->em->persist($job->getCompany());
        
        //$this->em->merge($company);
        //$this->em->merge($job);

        return $job;
    }
    public function findJobWithAsciiName($companyAsciiName,$jobAsciiName)
    {

        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->from("Company\Model\Company", "c")->where("j.asciiName=:jobId AND c.asciiName=:companyId");
        $qb->setParameter('jobId', $jobAsciiName);
        $qb->setParameter('companyId', $companyAsciiName);

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
