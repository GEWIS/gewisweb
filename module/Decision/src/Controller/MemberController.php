<?php

namespace Decision\Controller;

use Decision\Service\Decision;
use Decision\Service\Member;
use Decision\Service\MemberInfo;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use User\Service\User;

class MemberController extends AbstractActionController
{
    /**
     * @var Member
     */
    private $memberService;

    /**
     * @var MemberInfo
     */
    private $memberInfoService;

    /**
     * @var Decision
     */
    private $decisionService;

    /**
     * @var array
     */
    private $regulationsConfig;
    private User $userService;

    public function __construct(
        Member $memberService,
        MemberInfo $memberInfoService,
        Decision $decisionService,
        User $userService,
        array $regulationsConfig
    ) {
        $this->memberService = $memberService;
        $this->memberInfoService = $memberInfoService;
        $this->decisionService = $decisionService;
        $this->regulationsConfig = $regulationsConfig;
        $this->userService = $userService;
    }

    public function indexAction()
    {
        // Get the latest 3 meetings of each type and flatten result
        $meetingsCollection = [
            'AV' => array_column($this->decisionService->getPastMeetings(3, 'AV'), 0),
            'BV' => array_column($this->decisionService->getPastMeetings(3, 'BV'), 0),
            'VV' => array_column($this->decisionService->getPastMeetings(3, 'VV'), 0),
        ];

        $member = $this->userService->getIdentity()->getMember();

        return new ViewModel(
            [
                'member' => $member,
                'isActive' => $this->memberService->isActiveMember(),
                'upcoming' => $this->decisionService->getUpcomingMeeting(),
                'meetingsCollection' => $meetingsCollection,
            ]
        );
    }

    /**
     * Shown own information.
     */
    public function selfAction()
    {
        return new ViewModel($this->memberInfoService->getMembershipInfo());
    }

    /**
     * View information about a member.
     */
    public function viewAction()
    {
        $info = $this->memberInfoService->getMembershipInfo($this->params()->fromRoute('lidnr'));

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
            foreach ($this->memberService->searchMembersByName($name) as $member) {
                $members[] = [
                    'lidnr' => $member->getLidnr(),
                    'fullName' => $member->getFullname(),
                    'generation' => $member->getGeneration(),
                ];
            }

            return new JsonModel(
                [
                    'members' => $members,
                ]
            );
        }

        return new ViewModel([]);
    }

    /**
     * Determinues whether a member can be authorized without additional confirmation.
     */
    public function canAuthorizeAction()
    {
        $lidnr = $this->params()->fromQuery('q');
        $meeting = $this->decisionService->getLatestAV();

        if (!empty($lidnr) && !empty($meeting)) {
            $member = $this->memberService->findMemberByLidNr($lidnr);
            $canAuthorize = $this->memberService->canAuthorize($member, $meeting);

            if ($canAuthorize) {
                return new JsonModel(
                    [
                        'value' => true,
                    ]
                );
            }

            return new JsonModel(
                [
                    'value' => false,
                ]
            );
        }

        return new ViewModel([]);
    }

    /**
     * Show birthdays of members.
     */
    public function birthdaysAction()
    {
        return new ViewModel(
            [
                'members' => $this->memberService->getBirthdayMembers(7),
            ]
        );
    }

    /**
     * Action to go to dreamspark.
     */
    public function dreamsparkAction()
    {
        $url = $this->memberService->getDreamsparkUrl();

        return $this->redirect()->toUrl($url);
    }

    /**
     * Action to download regulations.
     */
    public function downloadRegulationAction()
    {
        $regulation = $this->params('regulation');
        if (isset($this->regulationsConfig['regulation'])) {
            $this->getResponse()->setStatusCode(404);
        }
        $path = $this->regulationsConfig[$regulation];

        return $this->redirect()->toUrl($this->url()->fromRoute('decision/files', ['path' => '']) . $path);
    }
}
