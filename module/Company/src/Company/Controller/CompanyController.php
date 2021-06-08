<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CompanyController extends AbstractActionController
{
    /**
     *
     * Action to display a list of all nonhidden companies
     *
     */
    public function listAction()
    {
        $companyService = $this->getCompanyService();
        $featuredPackage = $companyService->getFeaturedPackage();
        if ($featuredPackage === null) {
            return new ViewModel([
                'companyList' => $companyService->getCompanyList(),
                'translator' => $companyService->getTranslator(),
            ]);
        }

        return new ViewModel([
            'companyList' => $companyService->getCompanyList(),
            'translator' => $companyService->getTranslator(),
            'featuredCompany' => $featuredPackage->getCompany(),
            'featuredPackage' => $featuredPackage,
        ]);
    }

    public function showAction()
    {
        $companyService = $this->getCompanyService();
        $companyName = $this->params('slugCompanyName');
        $company = $companyService->getCompanyBySlugName($companyName);

        if (!is_null($company)) {
            if (!$company->isHidden()) {
                return new ViewModel([
                    'company' => $company,
                    'translator' => $companyService->getTranslator(),
                ]);
            }
        }

        return $this->notFoundAction();
    }

    /**
     *
     * Action that shows the 'company in the spotlight' and the article written by the company in the current language
     *
     */
    public function spotlightAction()
    {
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();

        $featuredPackage = $companyService->getFeaturedPackage();
        if (!is_null($featuredPackage)) {
            // jobs for a single company
            return new ViewModel([
                'company' => $featuredPackage->getCompany(),
                'featuredPackage' => $featuredPackage,
                'translator' => $translator,
            ]);
        }

        // There is no company is the spotlight, so throw a 404
        $this->getResponse()->setStatusCode(404);
    }


    /**
     * Put the highlighted jobs in front of the list.
     *
     * @param $jobs List of jobs
     *
     * @return array List of jobs with highlighted jobs in front
     */
    public function pushHighlightsUpfront($jobs, $lang) {
        $companyService = $this->getCompanyService();

        $highlightIds = $companyService->getHighlightsList($lang);

        $result1 = [];
        $result2 = [];
        foreach ($jobs as $job) {
            if (in_array($job->getId(), $highlightIds)) {
                array_push($result1, $job);
            } else {
                array_push($result2, $job);
            }
        }

        return array_merge($result1, $result2);
    }




    /**
     *
     * Action that displays a list of all jobs (facaturebank) or a list of jobs for a company
     *
     */
    public function jobListAction()
    {
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();
        if($this->params('category') == 'all') {
            // Retrieve all published jobs
            $jobs = $companyService->getAllJobs();

            // Shuffle order to avoid bias
            shuffle($jobs);

            // Put highlighted jobs in front
            $jobs = $this->pushHighlightsUpfront($jobs, $translator->getLocale());

            return new ViewModel([
                'translator' => $companyService->getTranslator(),
                'all' => true,
                'jobList' => $jobs
            ]);
        }
        $category = $companyService->categoryForSlug($this->params('category'));

        if (is_null($category)) {
            return $this->notFoundAction();
        }

        $viewModel = new ViewModel([
            'category' => $category,
            'translator' => $companyService->getTranslator(),
            'all' => false,
        ]);

        // A job can be a thesis/internship/etc.
        $jobCategory = ($category->getLanguageNeutralId() != null) ? $category->getSlug() : null;

        if ($companyName = $this->params('slugCompanyName', null)) {
            // Retrieve published jobs for one specific company
            $jobs = $companyService->getActiveJobList([
                'jobCategory' => $jobCategory,
                'companySlugName' => $companyName,
            ]);

            return $viewModel->setVariables([
                'jobList' => $jobs,
                'company' => $companyService->getCompanyBySlugName($companyName)
            ]);
        }

        // Retrieve all published jobs
        $jobs = $companyService->getActiveJobList([
            'jobCategory' => $jobCategory,
        ]);

        // Shuffle order to avoid bias
        shuffle($jobs);

        // Put highlighted jobs in front
        $jobs = $this->pushHighlightsUpfront($jobs, $translator->getLocale());

        return $viewModel->setVariables([
            'jobList' => $jobs
        ]);
    }

    /**
     *
     * Action to list a single job of a certain company
     *
     */
    public function jobsAction()
    {
        $companyService = $this->getCompanyService();
        $jobName = $this->params('slugJobName');
        $companyName = $this->params('slugCompanyName');
        $category = $companyService->categoryForSlug($this->params('category'));
        if ($jobName !== null) {
            $jobs = $companyService->getJobs([
                'companySlugName' => $companyName,
                'jobSlug' => $jobName,
                'jobCategory' => ($category->getLanguageNeutralId() !== null) ? $category->getSlug() : null
            ]);
            if (!empty($jobs)) {
                if ($jobs[0]->isActive()) {
                    return new ViewModel([
                        'job' => $jobs[0],
                        'translator' => $companyService->getTranslator(),
                        'category' => $category,
                    ]);
                }
            }

            return $this->notFoundAction();
        }

        return new ViewModel([
            'activeJobList' => $companyService->getActiveJobList(),
            'translator' => $companyService->getTranslator(),
        ]);
    }

    /**
     * Method that returns the service object for the company module.
     *
     *
     */
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('company_service_company');
    }
}
