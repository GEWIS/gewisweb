<?php

namespace Company\Service;

use Company\Mapper\{
    Category as CategoryMapper,
    Job as JobMapper,
    Label as LabelMapper,
};
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

/**
 * CompanyQuery service.
 */
class CompanyQuery
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var JobMapper
     */
    private JobMapper $jobMapper;

    /**
     * @var CategoryMapper
     */
    private CategoryMapper $categoryMapper;

    /**
     * @var LabelMapper
     */
    private LabelMapper $labelMapper;

    /**
     * @var AclService
     */
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        JobMapper $jobMapper,
        CategoryMapper $categoryMapper,
        LabelMapper $labelMapper,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->jobMapper = $jobMapper;
        $this->categoryMapper = $categoryMapper;
        $this->labelMapper = $labelMapper;
        $this->aclService = $aclService;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Returns all jobs with a $jobSlugName, owned by a company with a
     * $companySlugName, and a specific $category.
     *
     * @param array $dict
     * @return int|mixed|string
     */
    public function getJobs($dict)
    {
        if (array_key_exists('jobCategory', $dict) && null === $dict['jobCategory']) {
            $jobs = $this->jobMapper->findJobsWithoutCategory($this->translator->getTranslator()->getLocale());
            foreach ($jobs as $job) {
                $job->setCategory($this->categoryMapper
                    ->createNullCategory($this->translator->getTranslator()->getLocale(), $this->translator));
            }

            return $jobs;
        }
        $locale = $this->translator->getTranslator()->getLocale();
        $dict['language'] = $locale;

        return $this->jobMapper->findJob($dict);
    }

    /**
     * Returns all jobs that are active.
     *
     * @return array
     */
    public function getActiveJobList($dict = [])
    {
        $jobList = $this->getJobs($dict);
        $array = [];
        foreach ($jobList as $job) {
            if ($job->isActive()) {
                $array[] = $job;
            }
        }

        return $array;
    }

    /**
     * Returns all categories if $visible is false, only returns visible categories if $visible is true.
     *
     * @param bool $visible
     *
     * @return array
     */
    public function getCategoryList($visible)
    {
        if (!$visible) {
            if (!$this->aclService->isAllowed('listAllCategories', 'company')) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to list all job categories')
                );
            }

            return $this->categoryMapper->findAll();
        }

        if (!$this->aclService->isAllowed('listVisibleCategories', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list job categories'));
        }

        $categories = $this->categoryMapper->findVisibleCategories();

        return $this->filterCategories($categories);
    }

    /**
     * Filters out categories that are not used in active jobs.
     *
     * @param array $categories
     *
     * @return array
     */
    private function filterCategories(array $categories): array
    {
        $nonEmptyCategories = [];

        foreach ($categories as $category) {
            if (count($this->getActiveJobList(['jobCategoryId' => $category->getId()])) > 0) {
                $nonEmptyCategories[] = $category;
            }
        }

        return $nonEmptyCategories;
    }

    /**
     * Returns all labels if $visible is false, only returns visible labels if $visible is true.
     *
     * @param bool $visible
     *
     * @return array
     */
    public function getLabelList(bool $visible): array
    {
        if (!$visible) {
            if (!$this->aclService->isAllowed('listAllLabels', 'company')) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to list all job labels')
                );
            }

            return $this->labelMapper->findAll();
        }

        if (!$this->aclService->isAllowed('listVisibleLabels', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list job labels'));
        }

        $labels = $this->labelMapper->findVisibleLabels();

        return $this->filterLabels($labels);
    }

    /**
     * Filters out labels that are not used in active jobs.
     *
     * @param array $labels
     *
     * @return array
     */
    private function filterLabels(array $labels): array
    {
        $nonEmptyLabels = [];

        foreach ($labels as $label) {
            if (count($this->getActiveJobList(['jobCategoryId' => $label->getId()])) > 0) {
                $nonEmptyLabels[] = $label;
            }
        }

        return $nonEmptyLabels;
    }
}
