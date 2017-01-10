<?php

namespace Activity\Controller;

use Activity\Model\Activity;
use Activity\Service\Signup;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Activity\Form\ModifyRequest as RequestForm;
use Zend\View\Model\ViewModel;

class ActivityController extends AbstractActionController
{
    /**
     * View all activities.
     */
    public function indexAction()
    {
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $translatorService = $this->getServiceLocator()->get('activity_service_activityTranslator');
        $langSession = new SessionContainer('lang');

        $activities = $queryService->getUpcomingActivities();
        $translatedActivities = [];
        foreach ($activities as $activity){
            $translatedActivities[] = $translatorService->getTranslatedActivity($activity, $langSession->lang);
        }
        return ['activities' => $translatedActivities];
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $translatorService = $this->getServiceLocator()->get('activity_service_activityTranslator');
        $langSession = new SessionContainer('lang');
        $activityRequestSession = new SessionContainer('activityRequest');

        /** @var $activity Activity*/
        $activity = $queryService->getActivity($id);

        $translatedActivity = $translatorService->getTranslatedActivity($activity, $langSession->lang);
        $identity = $this->getServiceLocator()->get('user_role');
        /** @var Signup $signupService */
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $isAllowedToSubscribe = $signupService->isAllowedToSubscribe();

        $fields = $translatedActivity->getFields();
        $form = null;
        if ($isAllowedToSubscribe) {
            $form = $signupService->getForm($fields);
        }
        $subscriptionDeadLinePassed = $activity->getSubscriptionDeadline() < new \DateTime();
        $result = [
            'activity' => $translatedActivity,
            'signupOpen' => $activity->getCanSignUp() &&
            !$subscriptionDeadLinePassed &&
            $activity->getStatus() === Activity::STATUS_APPROVED,
            'isAllowedToSubscribe' => $isAllowedToSubscribe,
            'isSignedUp' => $isAllowedToSubscribe && $signupService->isSignedUp($translatedActivity, $identity->getMember()),
            'signupData' => $translatorService->getTranslatedSignedUpData($activity, $langSession->lang),
            'form' => $form,
            'signoffForm' => new RequestForm('activitysignoff', 'Unsubscribe'),
            'fields' => $fields,
        ];

        //Retrieve and clear the request status from the session, if it exists.
        if (isset($activityRequestSession->success)){
            $result['success'] = $activityRequestSession->success;
            unset($activityRequestSession->success);
            $result['message'] = $activityRequestSession->message;
            unset($activityRequestSession->message);
        }

        return $result;
    }

    /**
     * Create an activity.
     */
    public function createAction()
    {
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $form = $activityService->getForm();
        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $activityService->createActivity(
                    $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY),
                    $postData['language_dutch'],
                    $postData['language_english']
                );
                $view = new ViewModel();
                $view->setTemplate('activity/activity/createSuccess.phtml');
                return $view;
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
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        /** @var \Activity\Service\Signup $signupService */
        $signupService = $this->getServiceLocator()->get('activity_service_signup');

        /** @var  $activity Activity */
        $activity = $queryService->getActivity($id);

        $translator = $activityService->getTranslator();

        //Assure the form is used
        if (!$this->getRequest()->isPost()){
            $error = $translator->translate('Use the form to subscribe');
            $this->redirectActivityRequest($id, false, $error);
            return;
        }

        $subscriptionDeadLinePassed = $activity->getSubscriptionDeadline() < new \DateTime();

        // Assure you can sign up for this activity
        if (!$activity->getCanSignup() || $subscriptionDeadLinePassed || $activity->getStatus()!==Activity::STATUS_APPROVED) {
            $error = $translator->translate('You can not subscribe to this activity at this moment');
            $this->redirectActivityRequest($id, false, $error);
            return;
        }

        if (!$signupService->isAllowedToSubscribe()) {
            $error = $translator->translate('You need to log in to subscribe');
            $this->redirectActivityRequest($id, false, $error);
            return;
        }

        $form = $signupService->getForm($activity->getFields());
        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()){
            $error = $translator->translate('Wrong form');
            $this->redirectActivityRequest($id, false, $error);
            return;
        }

        $identity = $this->getServiceLocator()->get('user_service_user')->getIdentity();
        $user = $identity->getMember();

        //Assure the user is not subscribed yet
        if ($signupService->isSignedUp($activity, $user)) {
            $error = $translator->translate('You have already been subscribed for this activity');
            $this->redirectActivityRequest($id, false, $error);
            return;
        }

        $signupService->signUp($activity, $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY));
        $message = $translator->translate('Successfully subscribed');
        $this->redirectActivityRequest($id, true, $message);
    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {
        $id = (int) $this->params('id');
        /** @var \Activity\Service\Activity $activityService */
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        /** @var \Activity\Service\SignUp $signupService */
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $activity = $queryService->getActivity($id);
        $translator = $activityService->getTranslator();

        //Assure a form is used
        if (!$this->getRequest()->isPost()){
            $message = $translator->translate('Use the form to unsubscribe');
            $this->redirectActivityRequest($id, false, $message);
            return;
        }

        $form = new RequestForm('activitysignoff');
        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()){
            $message = $translator->translate('Wrong form');
            $this->redirectActivityRequest($id, false, $message);
            return;
        }

        if (!$signupService->isAllowedToSubscribe()) {
            $message = $translator->translate('You have to be logged in to subscribe for this activity');
            $this->redirectActivityRequest($id, false, $message);
            return;
        }

        $identity = $this->getServiceLocator()->get('user_service_user')->getIdentity();
        $user = $identity->getMember();

        if (!$signupService->isSignedUp($activity, $user)) {
            $message = $translator->translate('You are not subscribed for this activity!');
            $this->redirectActivityRequest($id, false, $message);
            return;
        }

        $signupService->signOff($activity, $user);
        $message = $translator->translate('Successfully unsubscribed');
        $this->redirectActivityRequest($id, true, $message);
    }

    /**
     * Redirects to the view of the activity with the given $id, where the
     * $error message can be displayed if the request was unsuccesful (i.e.
     * $success was false)
     *
     * @param int $id
     * @param boolean $success Whether the request was successful
     * @param string $message
     */
    protected function redirectActivityRequest($id, $success, $message)
    {
        $activityRequestSession = new SessionContainer('activityRequest');
        $activityRequestSession->success = $success;
        $activityRequestSession->message = $message;
        $this->redirect()->toRoute('activity/view', [
            'id' => $id,
        ]);
    }

    public function touchAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('key' => 'value'))
            ->setTerminal(true);

        return $viewModel;
    }
}
