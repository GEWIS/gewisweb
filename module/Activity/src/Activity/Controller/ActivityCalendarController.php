<?php

namespace Activity\Controller;

use Activity\Service\ActivityCalendar;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityCalendarController extends AbstractActionController
{

    public function indexAction()
    {
        $service = $this->getActivityCalendarService();
        $config = $service->getConfig();

        return new ViewModel([
            'options' => $service->getUpcomingOptions(),
            'editableOptions' => $service->getEditableUpcomingOptions(),
            'APIKey' => $config['google_api_key'],
            'calendarKey' => $config['google_calendar_key'],
            'success' => $this->getRequest()->getQuery('success', false),
            'canCreate' => $service->canCreateProposal()
        ]);
    }

    /**
     * Get the activity calendar service
     *
     * @return ActivityCalendar
     */
    private function getActivityCalendarService()
    {
        return $this->getServiceLocator()->get('activity_service_calendar');
    }

    public function deleteAction()
    {
        $service = $this->getActivityCalendarService();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $service->deleteOption($request->getPost()['option_id']);
            $this->redirect()->toRoute('activity_calendar');
        }
    }

    public function approveAction()
    {
        $service = $this->getActivityCalendarService();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $service->approveOption($request->getPost()['option_id']);
            $this->redirect()->toRoute('activity_calendar');
        }
    }

    public function createAction()
    {
        $service = $this->getActivityCalendarService();

        $form = $service->getCreateProposalForm();
        $success = null;

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            $success = $service->createProposal($postData);
            if ($success === false) {
                $this->getResponse()->setStatusCode(400);
                $form->setData($postData);
            }
            $this->redirect()->toRoute('activity_calendar', [], ['query' => ['success' => 'true']]);
        }

        $period = $service->getCurrentPeriod();

        return new ViewModel([
            'period' => $period,
            'form' => $form,
            'success' => $success,
        ]);
    }

    public function sendNotificationsAction()
    {
        $this->getActivityCalendarService()->sendOverdueNotifications();
    }
}
