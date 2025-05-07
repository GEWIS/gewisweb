<?php

declare(strict_types=1);

namespace Company\Model;

use Company\Model\Enums\CompanyPackageTypes;
use Company\Model\JobCategory as JobCategoryModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Override;

use function array_filter;
use function count;

/**
 * CompanyPackage model.
 */
#[Entity]
class CompanyJobPackage extends CompanyPackage
{
    /**
     * The package's jobs.
     *
     * @var Collection<array-key, Job>
     */
    #[OneToMany(
        targetEntity: Job::class,
        mappedBy: 'package',
        cascade: ['persist', 'remove'],
    )]
    #[OrderBy(['updatedAt' => 'DESC'])]
    protected Collection $jobs;

    public function __construct()
    {
        parent::__construct();

        $this->jobs = new ArrayCollection();
    }

    /**
     * Get the jobs in the package.
     *
     * @return Collection<array-key, Job>
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    /**
     * Get the jobs in the package, but without any that are actually update proposals.
     *
     * @return Collection<array-key, Job>
     */
    public function getJobsWithoutProposals(): Collection
    {
        return $this->jobs->filter(static function (Job $job) {
            return !$job->isUpdate();
        });
    }

    /**
     * Get the number of jobs in the package.
     *
     * @return int of jobs in the package
     */
    public function getNumberOfActiveJobs(?JobCategoryModel $category = null): int
    {
        return count($this->getJobsInCategory($category));
    }

    /**
     * Get the jobs that are part of the given category.
     *
     * @return Job[]
     */
    public function getJobsInCategory(?JobCategoryModel $category = null): array
    {
        $filter = static function (Job $job) use ($category) {
            if (null === $category) {
                return $job->isActive() && $job->isApproved() && !$job->isUpdate();
            }

            return $job->getCategory() === $category && $job->isActive() && $job->isApproved() && !$job->isUpdate();
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

    #[Override]
    public function getType(): CompanyPackageTypes
    {
        return CompanyPackageTypes::Job;
    }
}
