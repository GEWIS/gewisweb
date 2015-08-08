<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CompanyController extends AbstractActionController
{

    public function listAction()
    {
        $companyService = $this->getCompanyService();
        $companyName = $this->params('slugCompanyName');    
        if ($companyName != null) {
            $companies = $companyService->getCompaniesWithSlugName($companyName);
            if (count($companies) != 0){
                $vm = new ViewModel(array(
                    'company' => $companies[0],
                ));
            } else { 
                $vm = new ViewModel(); 
            }
        }
        else {
            $vm = new ViewModel(array(
                'companyList' => $companyService->getCompanyList(),
                'translator' => $companyService->getTranslator()
            ));
        }
        return $vm;

    }

    public function jobsAction()
    {
        $companyService = $this->getCompanyService();
        $jobName = $this->params('slugJobName');    
        $companyName = $this->params('slugCompanyName');    
        if ($jobName != null) {
            $jobs = $companyService->getJobsWithSlugName($companyName, $jobName);
            if (count($jobs) != 0){
                $vm = new ViewModel(array(
                    'job' => $jobs[0]
                ));
            }
            else {
                $vm = new ViewModel();
            }
        }

        else {
            $vm = new ViewModel(array(
                'activeJobList' => $companyService->getActiveJobList()
            ));
        }
        return $vm;

    }
    
    public function adminAction()
    {
        $companyService = $this->getCompanyService();
        
            $vm = new ViewModel(array(
                'companyList' => $companyService->getCompanyList()
            ));
        
        return $vm;

    }

    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get("company_service_company");
    }

}
