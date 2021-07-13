<?php

namespace Company\Service;

use Application\Service\AbstractAclService;
use Company\Mapper\Category;
use Company\Mapper\Job;
use Company\Mapper\Label;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * CompanyQuery service.
 */
class CompanyQuery extends AbstractACLService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

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

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        Job $jobMapper,
        Category $categoryMapper,
        Label $labelMapper
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->jobMapper = $jobMapper;
        $this->categoryMapper = $categoryMapper;
        $this->labelMapper = $labelMapper;
    }

    public function getRole()
    {
        return $this->userRole;
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
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     * @param mixed $category
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
     * @param $visible
     *
     * @return array
     */
    public function getCategoryList($visible)
    {
        if (!$visible) {
            if (!$this->isAllowed('listAllCategories')) {
                throw new NotAllowedException($this->translator->translate('You are not allowed to access the admin interface'));
            }
            $results = $this->categoryMapper->findAll();

            return $this->getUniqueInArray($results, function ($a) {
                return $a->getLanguageNeutralId();
            });
        }
        if (!$this->isAllowed('listVisibleCategories')) {
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
     * @param $visible
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
        if (!$this->isAllowed('listVisibleLabels')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list all labels'));
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

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get the default resource Id.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'company';
    }
}
