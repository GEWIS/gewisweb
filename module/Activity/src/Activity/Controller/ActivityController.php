<?php

namespace Activity\Controller;

use Activity\Model\Activity;
use Activity\Service\Signup;
use Zend\Mvc\Controller\AbstractActionController;
use Activity\Form\Activity as ActivityForm;
use Activity\Form\ActivitySignup as SignupForm;

class ActivityController extends AbstractActionController
{
    /**
     * View all activities.
     */
    public function indexAction()
    {
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activities = $activityService->getApprovedActivities();

        return ['activities' => $activities];
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');

        /** @var $activity Activity*/
        $activity = $activityService->getActivity($id);

        $identity = $this->getServiceLocator()->get('user_role');
        /** @var Signup $signupService */
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $fields = $activity->get('fields');
        $form = new SignupForm($fields);
        return [
            'activity' => $activity,
            'canSignUp' => $activity->canSignUp(),
            'isLoggedIn' => $identity !== 'guest',
            'isSignedUp' => $identity !== 'guest' && $signupService->isSignedUp($activity, $identity->getMember()),
            'signedUp' => $signupService->getSignedUpUsers($activity),
            'signupData' => $signupService->getSignedUpData($activity),
            'form' => $form,
            'fields' => $fields
        ];
    }

    /**
     * Create an activity.
     */
    public function createAction()
    {
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $form = new ActivityForm();
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($data['costs'] === '' && $data['costs_unknown'] != 1) {
                $data['costs'] = '-1';    // Hack. Because empty string is seen as 0
            }

            $form->setData($this->getRequest()->getPost());

            if ($form->isValid()) {
                //var_dump($form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY));//debug
                // the argument \Zend\Form\FormInterface::VALUES_AS_ARRAY might be needed...
                $activity = $activityService->createActivity();

                $this->redirect()->toRoute('activity/view', [
                    'id' => $activity->get('id'),
                ]);
            }
            else {
                echo 'Form is invalid!';
                echo var_dump($form->getInputFilter()->getMessages());
            }
        }

        return ['form' => $form];
    }

    /**
     * Signup for a activity.
     */
    public function signupAction()
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        /** @var  $activity Activity */
        $activity = $activityService->getActivity($id);
        
        $params = $this->viewAction();        
        //Assure the form is used
        if (!$this->getRequest()->isPost()){
            $params['error'] = 'Gebruik het formulier om in te schrijven';
            return $params;
        }
        
        // Assure you can sign up for this activity
        if (!$activity->canSignup()) {
            $params['error'] = 'Op dit moment kun je je niet inschrijven voor deze activiteit';
            return $params;
        }

        // Make sure the user is logged in
        $identity = $this->getServiceLocator()->get('user_role');
        if ($identity === 'guest') {
            $params['error'] = 'Je moet ingelogd zijn om je in te kunnen schrijven';
            return $params;
        }
        $form = new SignupForm($activity->get('fields'));
        $form->setData($this->getRequest()->getPost());
        
        //Assure the form is valid
        if (!$form->isValid()){
            $params['error'] = 'Verkeerd formulier';
            return $params;
        }
        $user = $identity->getMember();

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        if ($signupService->isSignedUp($activity, $user)) {
            $params['error'] = 'Je hebt je al ingeschreven voor deze activiteit';
        } else {
            $signupService->signUp($activity, $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY));
            $params['success'] = true;
        }
        return $params;
    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activity = $activityService->getActivity($id);

        // Make sure the user is logged in
        $identity = $this->getServiceLocator()->get('user_role');
        if ($identity === 'guest') {
            $params['error'] = 'Je moet ingelogd zijn om je uit te kunnen schrijven';

            return $params;
        }
        $user = $identity->getMember();

        $signupService = $this->getServiceLocator()->get('activity_service_signoff');
        if (!$signupService->isSignedUp($activity, $user)) {
            $params['error'] = 'Je hebt je al uitgeschreven voor deze activiteit';
        } else {
            $signupService->signOff($activity, $user);
            $params['success'] = true;
        }
        $params = $this->viewAction();

        return $params;
    }
}
