<?php

namespace Company\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Entity,
    OneToMany,
};

/**
 * CompanyPackage model.
 */
#[Entity]
class CompanyJobPackage extends CompanyPackage
{
    /**
     * The package's jobs.
     */
    #[OneToMany(
        targetEntity: Job::class,
        mappedBy: "package",
        cascade: ["persist", "remove"],
    )]
    protected Collection $jobs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->jobs = new ArrayCollection();
    }

    /**
     * Get the jobs in the package.
     *
     * @return Collection jobs in the package
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    /**
     * Get the number of jobs in the package.
     *
     * @param $category
     *
     * @return int of jobs in the package
     */
    public function getNumberOfActiveJobs($category = null): int
    {
        return count($this->getJobsInCategory($category));
    }

    /**
     * Get the jobs that are part of the given category.
     *
     * @param $category
     *
     * @return array
     */
    public function getJobsInCategory($category): array
    {
        $filter = function ($job) use ($category) {
            if (null === $category) {
                return $job->isActive();
            }
            if (null === $job->getCategory() && null === $category->getLanguageNeutralId()) {
                return $job->isActive();
            }
            if (null === $job->getCategory()) {
                return false;
            }

            return $job->getCategory()->getLanguageNeutralId() === $category->getLanguageNeutralId()
                && $job->isActive() && $job->getLanguage() === $category->getLanguage();
        };

        return array_filter($this->jobs->toArray(), $filter);
    }

    /**
     * Adds a job to the package.
     *
     * @param Job $job job to be added
     */
    public function addJob(Job $job): void
    {
        $this->jobs->add($job);
    }

    /**
     * Removes a job from the package.
     *
     * @param Job $job job to be removed
     */
    public function removeJob(Job $job): void
    {
        $this->jobs->removeElement($job);
    }
}
