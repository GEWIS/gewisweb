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

    public function dummyAction(){

    }

    /**
     * Get the member service.
     *
     * @return Decision\Service\companyAccount
     */
    public function getcompanyAccountService()
    {
        return $this->getServiceLocator()->get('decision_service_companyAccount');
    }




    public function hey(){
        return "hey";
    }


}
