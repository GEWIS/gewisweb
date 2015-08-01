<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MemberController extends AbstractActionController
{

    /**
     * Index action, shows all organs.
     */
    public function indexAction()
    {
        return new ViewModel($this->getMemberService()->getMembershipInfo());
    }

    /**
     * Search action, allows searching for members.
     */
    public function searchAction()
    {
        $name = $this->params()->fromQuery('q');
        $members = array();
        foreach ($this->getMemberService()->searchMembersByName($name) as $member) {
            //TODO: this returns a lot of data, much more than is needed in most cases.
            $members[] = $member->toArray();
        }

        return new JsonModel(array(
            'members' => $members
        ));
    }

    /**
     * Show birthdays of members.
     */
    public function birthdaysAction()
    {
        return new ViewModel(array(
            'members' => $this->getMemberService()->getBirthdayMembers(7)
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
