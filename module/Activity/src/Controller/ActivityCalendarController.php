<?php

namespace Activity\Controller;

use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Service\{
    AclService,
    ActivityCalendar as ActivityCalendarService,
    ActivityCalendarForm as ActivityCalendarFormService,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;

class ActivityCalendarController extends AbstractActionController
{
    /**
     * @var ActivityCalendarService
     */
    private ActivityCalendarService $calendarService;

    /**
     * @var ActivityCalendarFormService
     */
    private ActivityCalendarFormService $calendarFormService;

    private AclService $aclService;

    /**
     * @var ActivityCalendarProposalForm
     */
    private ActivityCalendarProposalForm $calendarProposalForm;

    /**
     * @var array
     */
    private array $calendarConfig;

    private Translator $translator;

    /**
     * ActivityCalendarController constructor.
     *
     * @param ActivityCalendarService $calendarService
     * @param ActivityCalendarFormService $calendarFormService
     * @param AclService $aclService
     * @param ActivityCalendarProposalForm $calendarProposalForm
     * @param array $calendarConfig
     * @param Translator $translator
     */
    public function __construct(
        ActivityCalendarService $calendarService,
        ActivityCalendarFormService $calendarFormService,
        AclService $aclService,
        ActivityCalendarProposalForm $calendarProposalForm,
        array $calendarConfig,
        Translator $translator
    ) {
        $this->calendarService = $calendarService;
        $this->calendarFormService = $calendarFormService;
        $this->aclService = $aclService;
        $this->calendarProposalForm = $calendarProposalForm;
        $this->calendarConfig = $calendarConfig;
        $this->translator = $translator;
    }

    public function indexAction()
    {
        $config = $this->calendarConfig;

        return new ViewModel(
            [
                'options' => $this->calendarService->getUpcomingOptions(),
                'editableOptions' => $this->calendarService->getEditableUpcomingOptions(),
                'APIKey' => $config['google_api_key'],
                'calendarKey' => $config['google_calendar_key'],
                'success' => $this->getRequest()->getQuery('success', false),
                'canCreate' => $this->calendarService->canCreateProposal(),
                'canApprove' => $this->calendarService->canApproveOption(),
            ]
        );
    }

    public function deleteAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->deleteOption($request->getPost()['option_id']);
            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function approveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->approveOption($request->getPost()['option_id']);
            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function createAction()
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create activity proposals')
            );
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->calendarProposalForm->setData($request->getPost()->toArray());

            if ($this->calendarProposalForm->isValid()) {
                if ($this->calendarService->createProposal($this->calendarProposalForm->getData())) {
                    return $this->redirect()->toRoute(
                        'activity_calendar',
                        [],
                        [
                            'query' => [
                                'success' => 'true',
                            ],
                        ],
                    );
                }
            }
        }

        $period = $this->calendarFormService->getCurrentPeriod();

        return new ViewModel(
            [
                'period' => $period,
                'form' => $this->calendarProposalForm,
            ]
        );
    }
}
