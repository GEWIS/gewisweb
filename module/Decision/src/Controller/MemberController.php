<?php

namespace Decision\Controller;

use Decision\Service\{
    AclService,
    Decision as DecisionService,
    Member as MemberService,
    MemberInfo as MemberInfoService,
};
use Decision\Model\Enums\MeetingTypes;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class MemberController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly MemberService $memberService,
        private readonly MemberInfoService $memberInfoService,
        private readonly DecisionService $decisionService,
        private readonly array $regulationsConfig,
    ) {
    }

    public function indexAction(): ViewModel
    {
        // Get the latest 3 meetings of each type and flatten result
        $meetingsCollection = [
            MeetingTypes::ALV->getAbbreviation($this->translator) => array_column(
                $this->decisionService->getPastMeetings(3, MeetingTypes::ALV),
                0,
            ),
            MeetingTypes::BV->getAbbreviation($this->translator) => array_column(
                $this->decisionService->getPastMeetings(3, MeetingTypes::BV),
                0,
            ),
            MeetingTypes::VV->getAbbreviation($this->translator) => array_column(
                $this->decisionService->getPastMeetings(3, MeetingTypes::VV),
                0,
            ),
        ];

        $member = $this->aclService->getIdentityOrThrowException()->getMember();

        return new ViewModel(
            [
                'member' => $member,
                'isActive' => $this->memberService->isActiveMember(),
                'upcoming' => $this->decisionService->getUpcomingAnnouncedMeetings(),
                'meetingsCollection' => $meetingsCollection,
            ]
        );
    }

    /**
     * Shown own information.
     */
    public function selfAction(): ViewModel
    {
        return new ViewModel($this->memberInfoService->getMembershipInfo());
    }

    /**
     * View information about a member.
     */
    public function viewAction(): ViewModel
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
    public function searchAction(): JsonModel|ViewModel
    {
        if (!$this->aclService->isAllowed('search', 'member')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to search for members.'));
        }

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
    public function canAuthorizeAction(): JsonModel|ViewModel
    {
        $lidnr = $this->params()->fromQuery('q');
        $meeting = $this->decisionService->getLatestALV();

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
    public function birthdaysAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('birthdays', 'member')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of birthdays.')
            );
        }

        return new ViewModel(
            [
                'members' => $this->memberService->getBirthdayMembers(7),
            ]
        );
    }

    /**
     * Action to download regulations.
     */
    public function downloadRegulationAction(): Response|ViewModel
    {
        $regulation = $this->params()->fromRoute('regulation');

        if (!isset($this->regulationsConfig[$regulation])) {
            return $this->notFoundAction();
        }

        $path = $this->regulationsConfig[$regulation];

        return $this->redirect()->toUrl($this->url()->fromRoute('decision/files', ['path' => '']) . $path);
    }
}
