<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController
{

    public function indexAction()
    {
        $companyService = $this->getCompanyService();
        
        $vm = new ViewModel(array(
            'company_list' => $companyService->getHiddenCompanyList()
        ));
        
        return $vm;

    }

    public function editAction()
    {
        $companyService = $this->getCompanyService();
        
        $companyName = $this->params('asciiCompanyName');    
        $jobName = $this->params('asciiJobName');    
        $vm = new ViewModel(array(
            'company_list' => $companyService->getEditableCompaniesWithAsciiName($companyName),
            'asciiJobName' => $jobName
        ));
        
        return $vm;

    }
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get("company_service_company");
    }

}
