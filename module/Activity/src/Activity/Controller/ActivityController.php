<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use \Activity\Model\Activity as ActivityModel;
use \Activity\Form\Activity as ActivityForm;

class ActivityController extends AbstractActionController {
    private $modelActivity;

    public function indexAction() {
        $activityService = $this->getServiceLocator()->get('ActivityService');
        $activities = $activityService->getAllActivities();
        return ['activities' => $activities];
    }

	public function viewAction() {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('ActivityService');
        $activity = $activityService->getActivity($id);
        return ['activity' => $activity];
	}

    public function createAction() {
        $form = new ActivityForm();
        if ($this->getRequest()->isPost()) {
            $activity = new ActivityModel();
            $form->setInputFilter($activity->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $em = $this->serviceLocator->get('Doctrine\ORM\EntityManager');
                $activity->create($form->getData());
                $em->persist($activity);
                $em->flush();
            }
        }
        return ['form' => $form];
    }

}
