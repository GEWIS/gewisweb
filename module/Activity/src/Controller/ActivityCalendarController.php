<?php

namespace Activity\Controller;

use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Service\{
    AclService,
    ActivityCalendar as ActivityCalendarService,
    ActivityCalendarForm as ActivityCalendarFormService,
};
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;

class ActivityCalendarController extends AbstractActionController
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var ActivityCalendarService
     */
    private ActivityCalendarService $calendarService;

    /**
     * @var ActivityCalendarFormService
     */
    private ActivityCalendarFormService $calendarFormService;

    /**
     * @var ActivityCalendarProposalForm
     */
    private ActivityCalendarProposalForm $calendarProposalForm;

    /**
     * @var array
     */
    private array $calendarConfig;

    /**
     * ActivityCalendarController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param ActivityCalendarService $calendarService
     * @param ActivityCalendarFormService $calendarFormService
     * @param ActivityCalendarProposalForm $calendarProposalForm
     * @param array $calendarConfig
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        ActivityCalendarService $calendarService,
        ActivityCalendarFormService $calendarFormService,
        ActivityCalendarProposalForm $calendarProposalForm,
        array $calendarConfig,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->calendarService = $calendarService;
        $this->calendarFormService = $calendarFormService;
        $this->calendarProposalForm = $calendarProposalForm;
        $this->calendarConfig = $calendarConfig;
    }

    public function indexAction(): ViewModel
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

    public function deleteAction(): Response|ViewModel
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->deleteOption($request->getPost()['option_id']);
            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function approveAction(): Response|ViewModel
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->approveOption($request->getPost()['option_id']);
            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function createAction(): Response|ViewModel
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
