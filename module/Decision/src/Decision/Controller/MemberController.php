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

        // Get the latest 5 meetings that have taken place
        $meetings = $decisionService->getMeetings(5, false);

        // Flatten array
        $meetings = array_map(function ($item) { return $item[0]; }, $meetings);

        return new ViewModel([
            'member'   => $this->identity()->getMember(),
            'meetings' => $meetings,
            'upcoming' => $decisionService->getLatestAV()
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
}
