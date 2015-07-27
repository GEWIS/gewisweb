<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MemberController extends AbstractActionController
{

    public function indexAction() {
        //TODO
    }
    /**
     * Search action, allows searching for members.
     */
    public function indexAction()
    {
        return new ViewModel(array(
            'member' => $this->getMemberService()->getMembershipInfo()
        ));
    }

    public function searchAction()
    {
        $name = $this->params()->fromRoute('name');
        return new JsonModel(array(
            'members' => $this->getMemberService()->findMemberByName($name)

        ));
    }

    /**
     * Get the member service.
     *
     * @return Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->getServiceLocator()->get('decision_service_member');
    }
}
