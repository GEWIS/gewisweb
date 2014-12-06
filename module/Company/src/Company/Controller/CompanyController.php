<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CompanyController extends AbstractActionController {

    public function listAction() {
        $companyService = $this->getCompanyService();
        $companyName = $this->params('asciiCompanyName');    
        if ($companyName != null){
	    $companies = $companyService->getCompaniesWithAsciiName($companyName);
            if (count($companies)!=0){
                $vm = new ViewModel(array(
                    'company' => $companies[0]
                ));
            }
            else{$vm = new ViewModel();}
        }
        else{
            $vm = new ViewModel(array(
                'company_list' => $companyService->getCompanyList()
            ));
        }
        return $vm;

    }

    public function jobsAction() {
        $companyService = $this->getCompanyService();
        $jobName = $this->params('asciiJobName');    
        $companyName = $this->params('asciiCompanyName');    
        if ($jobName != null){
	    $jobs = $companyService->getJobsWithAsciiName($companyName,$jobName);
            if (count($jobs)!=0){
                $vm = new ViewModel(array(
                    'job' => $jobs[0]
                ));
            }
            else{$vm = new ViewModel();}
        }

        else{
            $vm = new ViewModel(array(
                'active_job_list' => $companyService->getActiveJobList()
            ));
        }
        return $vm;

    }

    protected function getCompanyService() {
        return $this->getServiceLocator()->get("company_service_company") ;
    }

}
