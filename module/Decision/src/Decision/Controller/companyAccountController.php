<?php

namespace Decision\Controller;

use Doctrine\DBAL\Schema\View;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class companyAccountController extends AbstractActionController
{
    public function indexAction()

    {
//        if (!$this->getCompanyService()->isAllowed('view')) {
//            $translator = $this->getCompanyService()->getTranslator();
//            throw new \User\Permissions\NotAllowedException(
//                $translator->translate('You are not allowed to view this page')
//            );
//        }
        return new ViewModel();
    }


    public function profileAction() {
        return new ViewModel();
    }
    public function settingsAction() {
        return new ViewModel();
    }

    public function dummyAction(){
        return new ViewModel();
    }

    public function hey(){
        return "hey";
    }

    /**
    * Get the company service.
    *
    * @return Decision\Service\CompanyAccount
    */
    public function getCompanyService()
    {
        return $this->getServiceLocator()->get('decision_service_companyaccount');
    }


    public function vacanciesAction() {
        return new ViewModel();

        $decisionService = $this->getServiceLocator()->get('decision_service_decision');

        // Get the latest 3 meetings of each type and flatten result
//        $meetingsCollection = [
//            'AV' => array_column($decisionService->getPastMeetings(3, 'AV'), 0),
//            'BV' => array_column($decisionService->getPastMeetings(3, 'BV'), 0),
//            'VV' => array_column($decisionService->getPastMeetings(3, 'VV'), 0),
//        ];

        //$company = $this->identity()->getCompany();

        return new ViewModel([
            'vacancies' => $this->getcompanyAccountService()->getActiveVacancies(),
            //'isActive' => $this->getMemberService()->isActiveMember(),
            //'upcoming' => $decisionService->getUpcomingMeeting(),
            //'meetingsCollection' => $meetingsCollection,
        ]);
    }



    /**
     * Get the member service.
     *
     * @return Decision\Service\companyAccount
     */
    public function getCompanyAccountService()
    {
        return $this->getServiceLocator()->get('decision_service_companyAccount');
    }


}
