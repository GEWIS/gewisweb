<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityCalendarController extends AbstractActionController {

    public function indexAction() {
        $service = $this->getActivityCalendarService();
        $options = $service->getUpcomingOptions();
        $config = $service->getConfig();
        return new ViewModel([
            'options' => $options,
            'APIKey' => $config['google_api_key'],
            'calendarKey' => $config['google_calendar_key']
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