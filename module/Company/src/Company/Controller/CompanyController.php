<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CompanyController extends AbstractActionController {
	
	public function indexAction() {
	
        $companyService = $this->getCompanyService();
            
        $vm = new ViewModel(array(
            'company_list' => $companyService->getCompanyList()
        ));
	    return $vm;
	
	}
    
    public function jobsAction() {
        $companyService = $this->getCompanyService();
            
        $vm = new ViewModel(array(
            'active_job_list' => $companyService->getActiveJobList()
        ));
	    return $vm;
        
    }
    
    protected function getCompanyService() {
        return $this->getServiceLocator()->get("company_service_company") ;
    }
    
}
