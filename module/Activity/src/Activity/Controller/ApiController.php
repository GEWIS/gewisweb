<?php

namespace Activity\Controller;

use Activity\Model\Activity;
use Activity\Service\Signup;
use Zend\Mvc\Controller\AbstractActionController;
use Activity\Form\Activity as ActivityForm;
use Activity\Form\ActivitySignup as SignupForm;
use Zend\View\Model\JsonModel;

class ApiController extends AbstractActionController
{
    /**
     * List all activities.
     */
    public function listAction()
    {
        $activityService = $this->getActivityService();
        $activities = $activityService->getApprovedActivities();
        $activitiesArray = [];
        foreach ($activities as $activity) {
            $activitiesArray[] = $activity->toArray();
        }

        return new JsonModel($activitiesArray);
    }

    /**
     * Signup for a activity.
     */
    public function signupAction()
    {
        $id = (int) $this->params('id');

        $activityService = $this->getActivityService();
        $signupService = $this->getSignupService();

        $params = [];
        $params['success'] = false;
        //Assure the form is used
        if ($this->getRequest()->isPost() && $signupService->isAllowedToSubscribe()) {
            $activity = $activityService->getActivity($id);
            if ($activity->getFields()->count() == 0 && $activity->getCanSignup()) {
                $signupService->signUp($activity, []);
                $params['success'] = true;
            }
        }

        return new JsonModel($params);
    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {
        $id = (int) $this->params('id');

        $activityService = $this->getActivityService();
        $signupService = $this->getSignupService();

        $params = [];
        $params['success'] = false;

        $identity = $this->getServiceLocator()->get('user_role');
        $user = $identity->getMember();
        if ($this->getRequest()->isPost() && $signupService->isAllowedToSubscribe()) {
            $activity = $activityService->getActivity($id);
            if ($signupService->isSignedUp($activity, $user)) {
                $signupService->signOff($activity, $user);
                $params['success'] = true;
            }
        }

        return new JsonModel($params);
    }

    /**
     * Get all activities which the current user has subscribed to
     */
    public function signedupAction()
    {
        $activities = $this->getSignupService()->getSignedUpActivityIds();

        return new JsonModel([
            'activities' => $activities
        ]);
    }

    /**
     * Get the activity service
     *
     * @return \Activity\Service\Activity
     */
    private function getActivityService()
    {
        return $this->getServiceLocator()->get('activity_service_activity');
    }

    /**
     * Get the signup service
     *
     * @return \Activity\Service\Signup
     */
    private function getSignupService()
    {
        return $this->getServiceLocator()->get('activity_service_signup');
    }
}
