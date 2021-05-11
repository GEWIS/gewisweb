<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class companyaccountController extends AbstractActionController
{


    public function indexAction()
    {
        $decisionService = $this->getServiceLocator()->get('decision_service_decision');
        $company = "Phillips";

        return new ViewModel([
            //fetch the active vacancies of the logged in company
            'vacancies' => $this->getcompanyAccountService()->getActiveVacancies($company),
            'company' => $company
        ]);
    }

    public function dummyAction(){

    }

    /**
     * Get the CompanAccount service.
     *
     * @return Decision\Service\CompanyAccount
     */
    public function getcompanyAccountService()
    {
        return $this->getServiceLocator()->get('decision_service_companyAccount');
    }

}
