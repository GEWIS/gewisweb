<?php

namespace Activity\Controller;

use Activity\Form\ActivityCalendarProposal;
use Activity\Service\{AclService,
    ActivityCalendar as ActivityCalendarService,
    ActivityCalendarForm as ActivityCalendarFormService};
use Activity\Model\ActivityOptionProposal as ProposalModel;
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
     * @var ActivityCalendarProposal
     */
    private $calendarProposalForm;

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
     * @param array $calendarConfig
     */
    public function __construct(
        ActivityCalendarService $calendarService,
        ActivityCalendarFormService $calendarFormService,
        AclService $aclService,
        ActivityCalendarProposal $calendarProposalForm,
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
            $this->redirect()->toRoute('activity_calendar');
        }
    }

    public function approveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->approveOption($request->getPost()['option_id']);
            $this->redirect()->toRoute('activity_calendar');
        }
    }

    public function createAction()
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to create activity proposals.'));
        }

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            $this->calendarProposalForm->setData($postData);

            if (!$this->calendarProposalForm->isValid()) {
                $success = false;
            } else {
                $validatedData = $this->calendarProposalForm->getData();
                $success = $this->calendarService->createProposal($validatedData);
            }

            if (false === $success) {
                $this->getResponse()->setStatusCode(400);
                $this->calendarProposalForm->setData($postData);
            } else {
                $this->redirect()->toRoute('activity_calendar', [], ['query' => ['success' => 'true']]);
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
