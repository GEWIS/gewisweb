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
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activities = $activityService->getAllActivities();
        return ['activities' => $activities];
    }

    /**
     * View one activity
     */
    public function viewAction() {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activity = $activityService->getActivity($id);

        $identity =$this->identity();
        $user = is_null($identity) ? null : $identity->getMember();
        $signupService = $this->getServiceLocator()->get('activity_service_signup');

        return [
            'activity' => $activity,
            'canSignUp' => $activity->canSignUp(),
            'isLoggedIn' => $user != null,
            'isSignedUp' => !is_null($user) && $signupService->isSignedUp($activity, $user)
        ];
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
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activity = $activityService->getActivity($id);
        $params = $this->viewAction();


        // Assure you can sign up for this activity
        if (!$activity->canSignup()){
            $params['error'] = "Op dit moment kun je je niet inschrijven voor deze activiteit";
            return $params;
        }

        // Make sure the user is logged in
        $identity = $this->identity();
        if (is_null($identity)) {
            $params['error'] = 'Je moet ingelogd zijn om je in te kunnen schrijven';
            return $params;
        }
        $user = $identity->getMember();

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        if ($signupService->isSignedUp($activity, $user)) {
            $params['error'] = 'Je hebt je al ingeschreven voor deze activiteit';
            return $params;
        }

        $signupService->signUp($activity, $user);
        $params['success'] = true;
        return $params;
    }

}
