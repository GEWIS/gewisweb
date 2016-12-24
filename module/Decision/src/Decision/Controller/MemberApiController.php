<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class MemberApiController extends AbstractActionController
{

    public function lidnrAction()
    {
        $lidnr = $this->params()->fromRoute('lidnr');

        $member = $this->getMemberService()->findMemberByLidNr($lidnr);

        if ($member) {
            return new JsonModel($member->toApiArray());
        }

        return new JsonModel([]);
    }

    /**
     * Get the member service.
     *
     * @return \Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->getServiceLocator()->get('decision_service_member');
    }
}
