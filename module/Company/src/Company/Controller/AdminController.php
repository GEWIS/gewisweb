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

    public function saveAction()
    {
        $companyName = $this->params('asciiCompanyName');    
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService = $this->getCompanyService();
            $companyForm=$companyService->getCompanyForm();
            $companyForm->setData($request->getPost());

            // TODO: isValid does not work yet
            if ($companyForm->isValid()) {
                $company=$companyService->getEditableCompaniesWithAsciiName($companyName)[0]; // Assumes the company is found. However, we know that it is found because it has been found when loading the form.
                $company->exchangeArray($request->getPost()); // Temporary fix, bind does not work yet?
                $companyService->saveCompany();
            }
        }

        return $this->redirect()->toRoute('admin_company/default', array('action'=>'edit', 'asciiCompanyName'=>$companyName),array(),true);   
    }
    public function editAction()
    {
        $companyService = $this->getCompanyService();
        
        $companyName = $this->params('asciiCompanyName');    
        $jobName = $this->params('asciiJobName');    
        $companyForm=$companyService->getCompanyForm();
        $company_list = $companyService->getEditableCompaniesWithAsciiName($companyName);
        //echo($this->url()->fromRoute('admin_company/default',array('action'=>'save', 'asciiCompanyName'=>$companyName)));
        if (empty($company_list)){
            $company=null;
        }
        else {
            $company=$company_list[0];
            $companyForm->bind($company);
            $companyForm->setAttribute('action',$this->url()->fromRoute('admin_company/default',array('action'=>'save','asciiCompanyName'=>$companyName)));
        }
        $vm = new ViewModel(array(
            'company' => $company,
            'asciiJobName' => $jobName,
            'companyEditForm' => $companyForm,
        ));
        
        return $vm;

    }
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get("company_service_company");
    }

}
