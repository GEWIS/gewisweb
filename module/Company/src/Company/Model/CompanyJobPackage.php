<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

/**
 * CompanyPackage model.
 *
 * @ORM\Entity
 */
class CompanyJobPackage extends CompanyPackage
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->jobs = new ArrayCollection();
    }
    /**
     * The package's jobs.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\Job", mappedBy="package", cascade={"persist", "remove"})
     */
    protected $jobs;

    /**
     * Get the jobs in the package.
     *
     * @return array jobs in the package
     */
    public function getJobs()
    {
        return $this->jobs;
    }
    
    /**
     * Get the number of jobs in the package.
     *
     * @return number of jobs in the package
     */
    public function getNumberOfActiveJobs()
    {
        if (!$this->isActive()) {
            return 0;
        }
        $count = 0;
        foreach ($this->jobs as $job) {
            if ($job->getActive()) {
                $count ++;
            }
        }
        return $count;
    }


    /**
     * Adds a job to the package.
     *
     * @param Job $job job to be added
     */
    public function addJob(Job $job)
    {
        $this->jobs->add($job);
    }

    /**
     * Removes a job from the package.
     *
     * @param Job $job job to be removed
     */
    public function removeJob(Job $job)
    {
        $this->jobs->removeElement($job);
    }
}
