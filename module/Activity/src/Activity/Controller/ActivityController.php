<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use \Activity\Model\Activity as ActivityModel;
use \Activity\Form\Activity as ActivityForm;

class ActivityController extends AbstractActionController {
    private $modelActivity;

    /**
     * View all activities
     */
    public function indexAction() {
        $activityService = $this->getServiceLocator()->get('ActivityService');
        $activities = $activityService->getAllActivities();
        return ['activities' => $activities];
    }

    /**
     * View one activity
     */
    public function viewAction() {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('ActivityService');
        $activity = $activityService->getActivity($id);
        return ['activity' => $activity];
	}

    /**
     * Create an activity
     */
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

    /**
     * Signup for a activity
     */
    public function signupAction() {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('ActivityService');
        $activity = $activityService->getActivity($id);
        $params = $this->viewAction();

        // Assure you can sign up for this activity
        if (!$activity->canSignup()){
            $params['error'] = "Op dit moment kun je je niet inschrijven voor deze activiteit";
            return $params;
        }

        // Make sure the user is logged in
        $user = $this->identity()->getMember();
        if (is_null($user)) {
            $params['error'] = 'Je moet ingelogd zijn om je in te kunnen schrijven';
            return $params;
        }

        $signupService = $this->getServiceLocator()->get('SignupService');
        if ($signupService->isSignedUp($activity, $user)) {
            $params['error'] = 'Je hebt je al ingeschreven voor deze activiteit';
            return $params;
        }

        $signupService->signUp($activity, $user);
        $params['success'] = true;
        return $params;
    }

}
