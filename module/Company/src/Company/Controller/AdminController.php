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
    public function addCompanyAction()
    {
        $companyService = $this->getCompanyService();
        $companyForm=$companyService->getCompanyForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            if (!isset($companyName)){
                $companyName=$request->getPost()['asciiName'];
            }
            $companyService = $this->getCompanyService();
            $companyForm=$companyService->getCompanyForm();
            $companyForm->setData($request->getPost());

            // TODO: isValid does not work yet
            if ($companyForm->isValid()) {
                $company=$companyService->insertCompany();
                $company->exchangeArray($request->getPost()); // Temporary fix, bind does not work yet?
                $companyService->saveCompany();
                return $this->redirect()->toRoute('admin_company/default', array('action'=>'edit', 'asciiCompanyName'=>$companyName),array(),false);   
            }
        }
        //$company=$companyService->insertCompany();
        //$companyForm->bind($company);
        $companyForm->setAttribute('action',$this->url()->fromRoute('admin_company/default',array('action'=>'addCompany')));
        $vm = new ViewModel(array(
          //  'company' => $company,
            'companyEditForm' => $companyForm,
        ));
        
        return $vm;
        
    }
    public function addJobAction()
    {
        $companyService = $this->getCompanyService();
        $companyName = $this->params('asciiCompanyName');    
        
        $companyForm=$companyService->getJobForm();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            if (!isset($jobName)){
                $jobName=$request->getPost()['asciiName'];
            }
            $companyService = $this->getCompanyService();
            $companyForm=$companyService->getJobForm();
            $companyForm->setData($request->getPost());

            // TODO: isValid does not work yet
            if ($companyForm->isValid()) {
                $job=$companyService->insertJobForCompanyAsciiName($companyName);
                $job->exchangeArray($request->getPost()); 
                $companyService->saveCompany();
                return $this->redirect()->toRoute('admin_company/editCompany/editJob', array('asciiCompanyName'=>$companyName, 'jobName'=>$jobName),array(),true);   
            }
        }

        $companyForm->setAttribute('action',$this->url()->fromRoute('admin_company/editCompany/addJob',array('asciiCompanyName'=>$companyName)));
        $vm = new ViewModel(array(
          //  'company' => $company,
            'companyEditForm' => $companyForm,
        ));
        return $vm;
        
    }
    public function saveCompanyAction()
    {
        $companyName = $this->params('asciiCompanyName');    
        $request = $this->getRequest();
        if ($request->isPost()) {
            if (!isset($companyName)){
                $companyName=$request->getPost()['asciiName'];
            }
            $companyService = $this->getCompanyService();
            $companyForm=$companyService->getCompanyForm();
            $companyForm->setData($request->getPost());

            // TODO: isValid does not work yet
            if ($companyForm->isValid()) {
                $company=$companyService->getEditableCompaniesWithAsciiName($companyName)[0]; // Assumes the company is found. However, we know that it is found because it has been found when loading the form.
                $company->exchangeArray($request->getPost()); // Temporary fix, bind does not work yet?
                $companyService->saveCompany();
            }
            else{
                return $this->forward()->dispatch('Company\Controller\AdminController', array('action'=> 'addCompany','form'=>$companyForm));
            }
        }

        return $this->redirect()->toRoute('admin_company/default', array('action'=>'edit', 'asciiCompanyName'=>$companyName),array(),true);   
    }
    public function saveJobAction()
    {
        $jobName = $this->params('asciiJobName');    
        $asciiCompanyName = $this->params('asciiCompanyName');    
        $request = $this->getRequest();
        if ($request->isPost()) {
            if (!isset($jobName)){
                $jobName=$request->getPost()['asciiName'];
            }
            $companyService = $this->getCompanyService();
            $companyForm=$companyService->getJobForm();
            $companyForm->setData($request->getPost());

            // TODO: isValid does not work yet
            //if ($companyForm->isValid()) {
                $job=$companyService->insertJobForCompanyAsciiName($asciiCompanyName);
                $job->exchangeArray($request->getPost()); // Temporary fix, bind does not work yet?
                $companyService->saveCompany();
            //}
        }

        return $this->redirect()->toRoute('admin_company/default', array('action'=>'edit', 'asciiCompanyName'=>$asciiCompanyName, 'asciiJobName'=>$jobName),array(),true);   
    }
    public function editJobAction()
    {
        $companyService = $this->getCompanyService();
        
        $companyName = $this->params('asciiCompanyName');    
        $asciiCompanyName = $this->params('asciiCompanyName');    
        $jobName = $this->params('jobName');    
        $companyForm=$companyService->getJobForm();
        $company_list = $companyService->getEditableJobsWithAsciiName($jobName, $companyName);
        //echo($this->url()->fromRoute('admin_company/default',array('action'=>'save', 'asciiCompanyName'=>$companyName)));
        if (empty($company_list)){
            $company=null;
        echo "No job found";
        }
        else {
            $company=$company_list[0];
            $companyForm->bind($company);
            $companyForm->setAttribute('action',$this->url()->fromRoute('admin_company/editCompany/editJob',array('jobName' => $jobName, 'asciiCompanyName'=>$companyName)));
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            if (!isset($jobName)){
                $jobName=$request->getPost()['asciiName'];
            }
            $companyService = $this->getCompanyService();
            $companyForm=$companyService->getJobForm();
            $companyForm->setData($request->getPost());

            if ($companyForm->isValid()) {
                 echo "VALID";
                $job=$companyService->insertJobForCompanyAsciiName($asciiCompanyName);
                $job->exchangeArray($request->getPost()); // Temporary fix, bind does not work yet?
                $companyService->saveCompany();
            }
            else{
                echo "NOT VALID";
            }
            return $this->redirect()->toRoute('admin_company/editCompany/editJob', array('asciiCompanyName'=>$asciiCompanyName, 'asciiJobName'=>$jobName),array(),true);   
        }
        $return = $companyService->getJobsWithCompanyAsciiName($companyName);
        $vm = new ViewModel(array(
            'joblist' => $return, 
            'companyEditForm' => $companyForm,
        ));
        
        return $vm;

    }
    public function editCompanyAction()
    {
        $companyService = $this->getCompanyService();
        
        $companyName = $this->params('asciiCompanyName');    
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
        $return = $companyService->getJobsWithCompanyAsciiName($companyName);
        $vm = new ViewModel(array(
            'company' => $company,
            //'asciiJobName' => $jobName,
            'joblist' => $return, 
            'companyEditForm' => $companyForm,
        ));
        
        return $vm;

    }
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get("company_service_company");
    }

}
