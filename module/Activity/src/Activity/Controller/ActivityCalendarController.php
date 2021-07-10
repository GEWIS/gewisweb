<?php

namespace Activity\Controller;

use Activity\Service\ActivityCalendar;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityCalendarController extends AbstractActionController
{

    /**
     * @var ActivityCalendar
     */
    private $calendarService;

    /**
     * @var array
     */
    private $calendarConfig;

    public function __construct(ActivityCalendar $calendarService, array $calendarConfig)
    {
        $this->calendarService = $calendarService;
        $this->calendarConfig = $calendarConfig;
    }

    public function indexAction()
    {
        $config = $this->calendarConfig;

        return new ViewModel([
            'options' => $this->calendarService->getUpcomingOptions(),
            'editableOptions' => $this->calendarService->getEditableUpcomingOptions(),
            'APIKey' => $config['google_api_key'],
            'calendarKey' => $config['google_calendar_key'],
            'success' => $this->getRequest()->getQuery('success', false),
            'canCreate' => $this->calendarService->canCreateProposal(),
            'canApprove' => $this->calendarService->canApproveOption()
        ]);
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
        $form = $this->calendarService->getCreateProposalForm();

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            $success = $this->calendarService->createProposal($postData);

            if ($success === false) {
                $this->getResponse()->setStatusCode(400);
                $form->setData($postData);
            } else {
                $this->redirect()->toRoute('activity_calendar', [], ['query' => ['success' => 'true']]);
            }
        }

        $period = $this->calendarService->getCurrentPeriod();

        return new ViewModel([
            'period' => $period,
            'form' => $form,
        ]);
    }

    public function sendNotificationsAction()
    {
        $this->calendarService->sendOverdueNotifications();
    }
}
