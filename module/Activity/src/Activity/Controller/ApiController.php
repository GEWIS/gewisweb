<?php

namespace Activity\Controller;

use Activity\Service\Activity;
use Activity\Service\Signup;
use User\Permissions\NotAllowedException;
use Zend\Form\FormInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ApiController extends AbstractActionController
{
    /**
     * List all activities.
     */
    public function listAction()
    {
        $activityService = $this->getActivityService();
        if (!$activityService->isAllowed('list', 'activityApi')) {
            $translator = $activityService->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to access the activities through the API')
            );
        }

        $activities = $activityService->getUpcomingActivities();
        $activitiesArray = [];

        foreach ($activities as $activity) {
            $activitiesArray[] = $activity->toArray();
        }

        return new JsonModel($activitiesArray);
    }

    /**
     * Get the activity service
     *
     * @return Activity
     */
    private function getActivityService()
    {
        return $this->getServiceLocator()->get('activity_service_activityQuery');
    }

    /**
     * Signup for a activity.
     */
    public function signupAction()
    {
        $id = (int)$this->params('id');

        $activityService = $this->getActivityService();
        $signupService = $this->getSignupService();

        $params = [];
        $params['success'] = false;
        //Assure the form is used
        if ($this->getRequest()->isPost() && $signupService->isAllowedToSubscribe()) {
            $activity = $activityService->getActivity($id);
            $form = $signupService->getForm($activity->getFields());
            $form->setData($this->getRequest()->getPost());

            if ($activity->getCanSignup() && $form->isValid()) {
                $signupService->signUp($activity, $form->getData(FormInterface::VALUES_AS_ARRAY));
                $params['success'] = true;
            }
        }

        return new JsonModel($params);
    }

    /**
     * Get the signup service
     *
     * @return Signup
     */
    private function getSignupService()
    {
        return $this->getServiceLocator()->get('activity_service_signup');
    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {
        $id = (int)$this->params('id');

        $activityService = $this->getActivityService();
        $signupService = $this->getSignupService();

        $params = [];
        $params['success'] = false;

        $identity = $this->getServiceLocator()->get('user_service_user')->getIdentity();
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
}
