<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MemberController extends AbstractActionController
{

    public function indexAction()
    {
        $decisionService = $this->getServiceLocator()->get('decision_service_decision');

        // Get the latest 3 meetings of each type and flatten result
        $meetingsCollection = [
            'AV' => array_column($decisionService->getPastMeetings(3, 'AV'), 0),
            'BV' => array_column($decisionService->getPastMeetings(3, 'BV'), 0),
            'VV' => array_column($decisionService->getPastMeetings(3, 'VV'), 0),
        ];

        $member = $this->identity()->getMember();

        return new ViewModel([
            'member'             => $member,
            'isActive'           => $this->getMemberService()->isActiveMember(),
            'upcoming'           => $decisionService->getUpcomingMeeting(),
            'meetingsCollection' => $meetingsCollection,
        ]);
    }

    /**
     * Shown own information.
     */
    public function selfAction()
    {
        return new ViewModel($this->getMemberService()->getMembershipInfo());
    }

    /**
     * View information about a member.
     */
    public function viewAction()
    {
        $info = $this->getMemberService()->getMembershipInfo($this->params()->fromRoute('lidnr'));

        if (null === $info) {
            return $this->notFoundAction();
        }

        return new ViewModel($info);
    }

    /**
     * Search action, allows searching for members.
     */
    public function searchAction()
    {
        $name = $this->params()->fromQuery('q');

        if (!empty($name)) {
            $members = [];
            foreach ($this->getMemberService()->searchMembersByName($name) as $member) {
                $members[] = [
                    'lidnr'      => $member->getLidnr(),
                    'fullName'   => $member->getFullname(),
                    'generation' => $member->getGeneration()
                ];
            }

            return new JsonModel([
                'members' => $members
            ]);
        }

        return new ViewModel([]);
    }

    /**
     * Determinues whether a member can be authorized without additional confirmation
     */
    public function canAuthorizeAction()
    {
        $lidnr = $this->params()->fromQuery('q');
        $meeting = $this->getDecisionService()->getLatestAV();

        if (!empty($lidnr) && !empty($meeting)) {
            $member = $this->getMemberService()->findMemberByLidNr($lidnr);
            $canAuthorize = $this->getMemberService()->canAuthorize($member, $meeting);

            if ($canAuthorize) {
                return new JsonModel([
                    'value' => true
                ]);
            }
            return new JsonModel([
                'value' => false
            ]);
        }

        return new ViewModel([]);
    }

    /**
     * Show birthdays of members.
     */
    public function birthdaysAction()
    {
        return new ViewModel([
            'members' => $this->getMemberService()->getBirthdayMembers(7)
        ]);
    }

    /**
     * Action to go to dreamspark.
     */
    public function dreamsparkAction()
    {
        $url = $this->getMemberService()->getDreamsparkUrl();

        return $this->redirect()->toUrl($url);
    }

    /**
     * Action to download regulations.
     */
    public function downloadRegulationAction()
    {
        $regulation = $this->params("regulation");
        $response = $this->getMemberService()->getRegulationDownload($regulation);
        if ($response) {
            return $response;
        }

        $this->getResponse()->setStatusCode(404);
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

    /**
     * Get the decision service.
     */
    public function getDecisionService()
    {
        return $this->getServiceLocator()->get('decision_service_decision');
    }
}
