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
		$roles = $activityService->getOrgans();
        return ['activities' => $activities,
				'roles' => $roles];
    }

    /**
     * View one activity
     */
    public function viewAction() {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activity = $activityService->getActivity($id);

        $identity =$this->getServiceLocator()->get('user_role');
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        return [
            'activity' => $activity,
            'canSignUp' => $activity->canSignUp(),
            'isLoggedIn' => $identity !== 'guest',
            'isSignedUp' => $identity !== 'guest' && $signupService->isSignedUp($activity, $identity->getMember()),
			'signedUp' => $signupService->getSignedUp($activity)
        ];
	}

    /**
     * Create an activity
     */
    public function createAction() {
		$activityService = $this->getServiceLocator()->get('activity_service_activity');
		$form = new ActivityForm($activityService->getOrgans());
        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());

            if ($form->isValid()) {
                $activity = $activityService->createActivity($form->getData());

                $this->redirect()->toRoute('activity/view', array(
                    'id' => $activity->get('id')
                ));
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

        // Assure you can sign up for this activity
        if (!$activity->canSignup()){
            $params['error'] = "Op dit moment kun je je niet inschrijven voor deze activiteit";
            return $params;
        }

        // Make sure the user is logged in
        $identity =$this->getServiceLocator()->get('user_role');
        if ($identity === 'guest') {
            $params['error'] = 'Je moet ingelogd zijn om je in te kunnen schrijven';
            return $params;
        }
        $user = $identity->getMember();

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        if ($signupService->isSignedUp($activity, $user)) {
            $params['error'] = 'Je hebt je al ingeschreven voor deze activiteit';
        }else{
			$signupService->signUp($activity, $user);
			$params['success'] = true;
		}
		$params = $this->viewAction();
        return $params;
    }
	
	/**
     * Signup for a activity
     */
	public function signoffAction() {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activity = $activityService->getActivity($id);
        

        // Make sure the user is logged in
        $identity =$this->getServiceLocator()->get('user_role');
        if ($identity === 'guest') {
            $params['error'] = 'Je moet ingelogd zijn om je uit te kunnen schrijven';
            return $params;
        }
        $user = $identity->getMember();

        $signupService = $this->getServiceLocator()->get('activity_service_signoff');
        if (!$signupService->isSignedUp($activity, $user)) {
            $params['error'] = 'Je hebt je al uitgeschreven voor deze activiteit';
        }else{
			$signupService->signOff($activity, $user);
			$params['success'] = true;
		}
		$params = $this->viewAction();
        return $params;
    }

}
