<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityCalendarController extends AbstractActionController {

    public function indexAction() {
        $service = $this->getActivityCalendarService();
        $createdOption = null;
        $optionError = false;
        $request = $this->getRequest();
        if ($request->isPost()) {
            $createdOption = $service->createOption($request->getPost());
            if (!$createdOption) {
                $optionError = true;
            }
        }
        $form = $service->getCreateOptionForm();
        $options = $service->getUpcomingOptions();
        $config = $service->getConfig();
        return new ViewModel([
            'options' => $options,
            'APIKey' => $config['google_api_key'],
            'calendarKey' => $config['google_calendar_key'],
            'form' => $form,
            'optionError' => $optionError,
            'createdOption' => $createdOption
        ]);
    }

    /**
     * Get the activity calendar service
     *
     * @return \Activity\Service\ActivityCalendar
     */
    private function getActivityCalendarService()
    {
        return $this->getServiceLocator()->get('activity_service_calendar');
    }
}