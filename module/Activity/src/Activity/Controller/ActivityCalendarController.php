<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityCalendarController extends AbstractActionController {

    public function indexAction() {
        $data = json_decode(file_get_contents('https://www.googleapis.com/calendar/v3/calendars/v23gq10ij44fhdsfdof1s613ak@group.calendar.google.com/events?key=AIzaSyB7z5HihqcDIdxFGGiunRq8GZjGCG_Wmi8&maxResults=2500'));

        return new ViewModel(['data' => $data]);
    }
}