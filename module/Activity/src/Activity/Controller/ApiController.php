<?php

namespace Activity\Controller;

use Activity\Service\ActivityQuery;
use Activity\Service\Signup;
use User\Permissions\NotAllowedException;
use Zend\Form\FormInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ApiController extends AbstractActionController
{

    /**
     * @var ActivityQuery
     */
    private $activityQueryService;

    /**
     * @var Signup
     */
    private $signupService;

    public function __construct(ActivityQuery $activityQueryService, Signup $signupService)
    {
        $this->activityQueryService = $activityQueryService;
        $this->signupService = $signupService;
    }

    /**
     * List all activities.
     */
    public function listAction()
    {
        if (!$this->activityQueryService->isAllowed('list', 'activityApi')) {
            $translator = $this->activityQueryService->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to access the activities through the API')
            );
        }

        $activities = $this->activityQueryService->getUpcomingActivities();
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

        $params = [];
        $params['success'] = false;
        //Assure the form is used
        if ($this->getRequest()->isPost() && $this->signupService->isAllowedToSubscribe()) {
            $activity = $this->activityQueryService->getActivity($id);
            $form = $this->signupService->getForm($activity->getFields());
            $form->setData($this->getRequest()->getPost());

            if ($activity->getCanSignup() && $form->isValid()) {
                $this->signupService->signUp($activity, $form->getData(FormInterface::VALUES_AS_ARRAY));
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

        $params = [];
        $params['success'] = false;

        $identity = $this->getServiceLocator()->get('user_service_user')->getIdentity();
        $user = $identity->getMember();
        if ($this->getRequest()->isPost() && $this->signupService->isAllowedToSubscribe()) {
            $activity = $this->activityQueryService->getActivity($id);
            if ($this->signupService->isSignedUp($activity, $user)) {
                $this->signupService->signOff($activity, $user);
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
        $activities = $this->signupService->getSignedUpActivityIds();

        return new JsonModel([
            'activities' => $activities
        ]);
    }
}
