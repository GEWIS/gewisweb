<?php

namespace Company\Service;

use Company\Mapper\Category;
use Company\Mapper\Job;
use Company\Mapper\Label;
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
    private $translator;

    /**
     * @var Job
     */
    private $jobMapper;

    /**
     * @var Category
     */
    private $categoryMapper;

    /**
     * @var Label
     */
    private $labelMapper;
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        Job $jobMapper,
        Category $categoryMapper,
        Label $labelMapper,
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
    public function getTranslator()
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
            $jobs = $this->jobMapper->findJobsWithoutCategory($this->translator->getLocale());
            foreach ($jobs as $job) {
                $job->setCategory($this->categoryMapper
                    ->createNullCategory($this->translator->getLocale(), $this->translator));
            }

            return $jobs;
        }
        $locale = $this->translator->getLocale();
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
     * Returns all categories if $visible is false, only returns visible categories if $visible is false.
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
                    $this->translator->translate('You are not allowed to access the admin interface')
                );
            }
            $results = $this->categoryMapper->findAll();

            return $this->getUniqueInArray($results, function ($a) {
                return $a->getLanguageNeutralId();
            });
        }
        if (!$this->aclService->isAllowed('listVisibleCategories', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list all categories'));
        }

        $categories = $this->categoryMapper->findVisibleCategoryByLanguage($this->translator->getLocale());
        $jobsWithoutCategory = $this->jobMapper->findJobsWithoutCategory($this->translator->getLocale());
        $filteredCategories = $this->filterCategories($categories);
        $noVacancyCategory = count(array_filter($filteredCategories, function ($el) {
            return 'jobs' == $el->getSlug();
        }));

        if (count($jobsWithoutCategory) > 0 && 0 == $noVacancyCategory) {
            $filteredCategories[] = $this->categoryMapper
                ->createNullCategory($this->translator->getLocale(), $this->translator);
        }

        return $filteredCategories;
    }

    /**
     * Filters out categories that are not used in active jobs.
     *
     * @param array $categories
     *
     * @return array
     */
    private function filterCategories($categories)
    {
        $nonemptyCategories = [];
        foreach ($categories as $category) {
            if (count($this->getActiveJobList(['jobCategoryId' => $category->getId()])) > 0) {
                $nonemptyCategories[] = $category;
            }
        }

        return $nonemptyCategories;
    }

    /**
     * Returns all labels if $visible is false, only returns visible labels if $visible is false.
     *
     * @param bool $visible
     *
     * @return array
     */
    public function getLabelList($visible)
    {
        if (!$visible) {
            $results = $this->labelMapper->findAll();

            return $this->getUniqueInArray($results, function ($a) {
                return $a->getLanguageNeutralId();
            });
        }

        $labels = $this->labelMapper->findVisibleLabelByLanguage($this->translator->getLocale());

        return $this->filterLabels($labels);
    }

    /**
     * Filters out labels that are not used in active jobs.
     *
     * @param array $labels
     *
     * @return array
     */
    private function filterLabels($labels)
    {
        $nonemptyLabels = [];
        foreach ($labels as $label) {
            if (count($this->getActiveJobList(['jobCategoryId' => $label->getId()])) > 0) {
                $nonemptyLabels[] = $label;
            }
        }

        return $nonemptyLabels;
    }

    private static function getUniqueInArray($array, $callback)
    {
        $tempResults = [];
        $resultArray = [];
        foreach ($array as $x) {
            $newVar = $callback($x);
            if (!array_key_exists($newVar, $tempResults)) {
                $resultArray[] = $x;
                $tempResults[$newVar] = $x;
            }
        }

        return $resultArray;
    }
}
