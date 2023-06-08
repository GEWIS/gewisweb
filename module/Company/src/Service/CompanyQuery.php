<?php

declare(strict_types=1);

namespace Company\Service;

use Company\Mapper\Category as CategoryMapper;
use Company\Mapper\Job as JobMapper;
use Company\Mapper\Label as LabelMapper;
use Company\Model\Job as JobModel;
use Company\Model\JobCategory as JobCategoryModel;
use Company\Model\JobLabel as JobLabelModel;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

use function array_filter;
use function count;

/**
 * CompanyQuery service.
 */
class CompanyQuery
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly JobMapper $jobMapper,
        private readonly CategoryMapper $categoryMapper,
        private readonly LabelMapper $labelMapper,
    ) {
    }

    /**
     * Get the translator.
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Returns all jobs with a $jobSlugName, owned by a company with a
     * $companySlugName, and a specific $category.
     *
     * @return JobModel[]
     */
    public function getJobs(
        ?int $jobCategoryId = null,
        ?string $jobCategorySlug = null,
        ?int $jobLabelId = null,
        ?string $jobSlugName = null,
        ?string $companySlugName = null,
    ): array {
        return $this->jobMapper->findJob(
            jobCategoryId: $jobCategoryId,
            jobCategorySlug: $jobCategorySlug,
            jobLabelId: $jobLabelId,
            jobSlugName: $jobSlugName,
            companySlugName: $companySlugName,
        );
    }

    /**
     * Returns all jobs that are active.
     *
     * @return JobModel[]
     */
    public function getActiveJobList(
        ?int $jobCategoryId = null,
        ?string $jobCategorySlug = null,
        ?int $jobLabelId = null,
        ?string $jobSlugName = null,
        ?string $companySlugName = null,
    ): array {
        $jobList = $this->getJobs(
            jobCategoryId: $jobCategoryId,
            jobCategorySlug: $jobCategorySlug,
            jobLabelId: $jobLabelId,
            jobSlugName: $jobSlugName,
            companySlugName: $companySlugName,
        );

        return array_filter($jobList, static function ($job) {
            return $job->isActive() && $job->isApproved();
        });
    }

    /**
     * Returns all categories if $visible is false, only returns visible categories if $visible is true.
     *
     * @return JobCategoryModel[]
     */
    public function getCategoryList(bool $visible): array
    {
        if (!$visible) {
            if (!$this->aclService->isAllowed('listAll', 'jobCategory')) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to list all job categories'),
                );
            }

            return $this->categoryMapper->findAll();
        }

        if (!$this->aclService->isAllowed('list', 'jobCategory')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list job categories'));
        }

        $categories = $this->categoryMapper->findVisibleCategories();

        return $this->filterCategories($categories);
    }

    /**
     * Filters out categories that are not used in active jobs.
     *
     * @param JobCategoryModel[] $categories
     *
     * @return JobCategoryModel[]
     */
    private function filterCategories(array $categories): array
    {
        $nonEmptyCategories = [];

        foreach ($categories as $category) {
            if (count($this->getActiveJobList(jobCategoryId: $category->getId())) <= 0) {
                continue;
            }

            $nonEmptyCategories[] = $category;
        }

        return $nonEmptyCategories;
    }

    /**
     * Returns all labels if $visible is false, only returns visible labels if $visible is true.
     *
     * @return JobLabelModel[]
     */
    public function getLabelList(bool $visible): array
    {
        if (!$visible) {
            if (!$this->aclService->isAllowed('listAll', 'jobLabel')) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to list all job labels'),
                );
            }

            return $this->labelMapper->findAll();
        }

        if (!$this->aclService->isAllowed('list', 'jobLabel')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list job labels'));
        }

        return $this->filterLabels($this->labelMapper->findAll());
    }

    /**
     * Filters out labels that are not used in active jobs.
     *
     * @param JobLabelModel[] $labels
     *
     * @return JobLabelModel[]
     */
    private function filterLabels(array $labels): array
    {
        $nonEmptyLabels = [];

        foreach ($labels as $label) {
            if (count($this->getActiveJobList(jobLabelId: $label->getId())) <= 0) {
                continue;
            }

            $nonEmptyLabels[] = $label;
        }

        return $nonEmptyLabels;
    }
}
