<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityController extends AbstractActionController {
    private $modelActivity;
    public function getModels() {
        $sm = $this->getServiceLocator();
        $this->modelActivity = $sm->get('Activity\Model\Activity');
    }
	public function indexAction() {
	}

    public function createAction() {
        print_r($this->modelActivity);
        die;
        if ($this->getRequest()->getMethod() == 'POST') {
            $activity = $this->modelActivity->create([
                'name' => '',
                'startDate' => '',
                'endDate' => '',
                'location' => '',
                'costs' => ''
            ]);
            print_r($activity);
        }
        return new ViewModel();
    }

}
