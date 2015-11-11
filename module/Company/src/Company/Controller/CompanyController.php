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
        $companyName = $this->params('slugCompanyName');
        if ($companyName != null) {
            $companies = $companyService->getCompaniesWithSlugName($companyName);
            if (count($companies) != 0) {
                $vm = new ViewModel([
                    'company' => $companies[0],
                    'translator' => $companyService->getTranslator(),
                ]);
            } else {
                $vm = new ViewModel([
                    'translator' => $companyService->getTranslator(),
                ]);
            }
        } else {
            $vm = new ViewModel([
                'companyList' => $companyService->getCompanyList(),
                'translator' => $companyService->getTranslator(),
            ]);
        }

        return $vm;
    }
    /**
     *
     * Action that displays a list of all jobs (facaturebank)
     *
     */
    public function jobListAction()
    {
        $companyService = $this->getCompanyService();
        $vm = new ViewModel([
            'jobList' => $companyService->getJobList(),
            'translator' => $companyService->getTranslator(),
        ]);

        return $vm;
    }

    /**
     *
     * Action to list jobs of a certain company
     *
     */
    public function jobsAction()
    {
        $companyService = $this->getCompanyService();
        $jobName = $this->params('slugJobName');
        $companyName = $this->params('slugCompanyName');
        if ($jobName != null) {
            $jobs = $companyService->getJobsWithSlugName($companyName, $jobName);
            if (count($jobs) != 0) {
                $vm = new ViewModel([
                    'job' => $jobs[0],
                ]);
            } else {
                $vm = new ViewModel();
            }
        } else {
            $vm = new ViewModel([
                'activeJobList' => $companyService->getActiveJobList(),
            ]);
        }

        return $vm;
    }

    public function adminAction()
    {
        $companyService = $this->getCompanyService();

        $vm = new ViewModel([
                'companyList' => $companyService->getCompanyList(),
            ]);

        return $vm;
    }

    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('company_service_company');
    }
}
