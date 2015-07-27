<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MemberController extends AbstractActionController
{
    
    public function indexAction()
    {
        return new ViewModel(array(
            'member' => $this->getMemberService()->getMembershipInfo()
        ));
    }

    /**
     * Search action, allows searching for members.
     */
    public function searchAction()
    {
        $name = $this->params()->fromRoute('name');
        $members = array();
        foreach($this->getMemberService()->findMemberByName($name) as $member) {
            //TODO: this returns a lot of data, much more than is needed in most cases.
            $members[] = $member->toArray();
        }
        return new JsonModel(array(
            'members' => $members
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
