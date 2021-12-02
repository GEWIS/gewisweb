<?php

namespace Decision\Controller;

use Decision\Service\{
    AclService,
    Decision as DecisionService,
    Member as MemberService,
    MemberInfo as MemberInfoService,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};

class MemberController extends AbstractActionController
{
    /**
     * @var MemberService
     */
    private MemberService $memberService;

    /**
     * @var MemberInfoService
     */
    private MemberInfoService $memberInfoService;

    /**
     * @var DecisionService
     */
    private DecisionService $decisionService;

    /**
     * @var array
     */
    private array $regulationsConfig;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * MemberController constructor.
     *
     * @param MemberService $memberService
     * @param MemberInfoService $memberInfoService
     * @param DecisionService $decisionService
     * @param array $regulationsConfig
     * @param AclService $aclService
     */
    public function __construct(
        MemberService $memberService,
        MemberInfoService $memberInfoService,
        DecisionService $decisionService,
        array $regulationsConfig,
        AclService $aclService
    ) {
        $this->memberService = $memberService;
        $this->memberInfoService = $memberInfoService;
        $this->decisionService = $decisionService;
        $this->regulationsConfig = $regulationsConfig;
        $this->aclService = $aclService;
    }

    public function indexAction()
    {
        // Get the latest 3 meetings of each type and flatten result
        $meetingsCollection = [
            'AV' => array_column($this->decisionService->getPastMeetings(3, 'AV'), 0),
            'BV' => array_column($this->decisionService->getPastMeetings(3, 'BV'), 0),
            'VV' => array_column($this->decisionService->getPastMeetings(3, 'VV'), 0),
        ];

        $member = $this->aclService->getIdentityOrThrowException()->getMember();

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
            return new JsonModel(
                [
                    'members' => $this->memberService->searchMembersByName($name),
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
