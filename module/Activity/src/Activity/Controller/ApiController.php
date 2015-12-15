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
     * View all activities.
     */
    public function listAction()
    {
        $activityService = $this->getActivityService();
        $activities = $activityService->getApprovedActivities();
        $activitiesArray = [];
        foreach($activities as $activity) {
            $activitiesArray[] = $activity->toArray();
        }

        return new JsonModel($activitiesArray);
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {

    }

    /**
     * Signup for a activity.
     */
    public function signupAction()
    {

    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {

    }

    private function getActivityService()
    {
        return $this->getServiceLocator()->get('activity_service_activity');
    }
}
