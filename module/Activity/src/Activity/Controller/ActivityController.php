<?php

namespace Activity\Controller;

use Activity\Model\Activity;
use Activity\Service\Signup;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Activity\Form\ModifyRequest as RequestForm;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\Parameters;

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
        $activities = $queryService->getUpcomingActivities($this->params('category'));
        $translatedActivities = [];
        foreach ($activities as $activity){
            $translatedActivities[] = $translatorService->getTranslatedActivity($activity, $langSession->lang);
        }
        return ['activities' => $translatedActivities, 'category' => $this->params('category')];
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
        $activitySession = new SessionContainer('activityRequest');
        $externalSession = new SessionContainer('externalActivityRequest');
        /** @var $activity Activity*/
        $activity = $queryService->getActivity($id);

        $translatedActivity = $translatorService->getTranslatedActivity($activity, $langSession->lang);
        $identity = $this->getServiceLocator()->get('user_role');
        /** @var Signup $signupService */
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $isAllowedToSubscribe = $signupService->isAllowedToSubscribe();

        $fields = $translatedActivity->getFields();
        $form = $this->prepareSignupForm($fields, $activitySession, $externalSession);
        $isSignedUp = false;
        if ($signupService->isAllowedToInternalSubscribe()) {
            $isSignedUp = $isAllowedToSubscribe
                && $signupService->isSignedUp($translatedActivity, $identity->getMember());
        }
        $subscriptionDeadLinePassed = $activity->getSubscriptionDeadline() < new \DateTime();
        $result = [
            'activity' => $translatedActivity,
            'signupOpen' => $activity->getCanSignUp() &&
            !$subscriptionDeadLinePassed &&
            $activity->getStatus() === Activity::STATUS_APPROVED,
            'isAllowedToSubscribe' => $isAllowedToSubscribe,
            'isSignedUp' => $isSignedUp,
            'signupData' => $signupService->isAllowedToViewSubscriptions() ?
                $translatorService->getTranslatedSignedUpData($activity, $langSession->lang) :
                null,
            'form' => $form,
            'signoffForm' => new RequestForm('activitysignoff', 'Unsubscribe'),
            'fields' => $fields,
            'memberSignups' => $signupService->getNumberOfSubscribedMembers($activity),
        ];

        //Retrieve and clear the request status from the session, if it exists.
        if (isset($activitySession->success)) {
            $result['success'] = $activitySession->success;
            unset($activitySession->success);
            $result['message'] = $activitySession->message;
            unset($activitySession->message);
        }
        if (isset($externalSession->success)) {
            $result['success'] = $externalSession->success;
            unset($externalSession->success);
            $result['message'] = $externalSession->message;
            unset($externalSession->message);
        }

        return $result;
    }

    /**
     * Get the appropriate signup form.
     *
     * @param type $fields
     * @param type $activitySession
     * @param type $externalSession
     * @return type $form
     */
    protected function prepareSignupForm($fields, & $activitySession, & $externalSession)
    {
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        if ($signupService->isAllowedToSubscribe()) {
            $form = $signupService->getForm($fields);
            if (isset($activitySession->signupData)) {
                $form->setData(new Parameters($activitySession->signupData));
                $form->isValid();
                unset($activitySession->signupData);
            }
            return $form;
        }
        if ($signupService->isAllowedToExternalSubscribe()) {
            $form = $signupService->getExternalForm($fields);
            if (isset($externalSession->signupData)) {
                $form->setData(new Parameters($externalSession->signupData));
                $form->isValid();
                unset($externalSession->signupData);
            }
            return $form;
        }
        return null;
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
        $postData = $this->getRequest()->getPost();
        $form->setData($postData);

        //Assure the form is valid
        if (!$form->isValid()){
            $error = $translator->translate('Invalid form');
            $activityRequestSession = new SessionContainer('activityRequest');
            $activityRequestSession->signupData = $postData->toArray();
            $this->redirectActivityRequest($id, false, $error, $activityRequestSession);
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

    public function externalSignupAction()
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $signupService = $this->getServiceLocator()->get('activity_service_signup');

        $activity = $queryService->getActivity($id);

        $translator = $activityService->getTranslator();

        //Assure the form is used
        if (!$this->getRequest()->isPost()) {
            $error = $translator->translate('Use the form to subscribe');
            $this->redirectActivityRequest($id, false, $error);
            return;
        }

        $form = $signupService->getExternalForm($activity->getFields());
        $postData = $this->getRequest()->getPost();
        $form->setData($postData);

        //Assure the form is valid
        if (!$form->isValid()) {
            $error = $translator->translate('Invalid form');
            $activityRequestSession = new SessionContainer('externalActivityRequest');
            $activityRequestSession->signupData = $postData->toArray();
            $this->redirectActivityRequest($id, false, $error, $activityRequestSession);
            return;
        }

        $formData = $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY);
        $fullName = $formData['fullName'];
        unset($formData['fullName']);
        $email = $formData['email'];
        unset($formData['email']);
        $signupService->externalSignUp($activity, $fullName, $email, $formData);
        $message = $translator->translate('Successfully subscribed as external participant');
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
    protected function redirectActivityRequest($id, $success, $message, $session = null)
    {
        if (is_null($session)) {
            $session = new SessionContainer('activityRequest');
        }
        $session->success = $success;
        $session->message = $message;
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



    /**
     * Display all the finished activities in a school year
     *
     * @return ViewModel
     */
    public function archiveAction()
    {

        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $translatorService = $this->getServiceLocator()->get('activity_service_activityTranslator');
        $langSession = new SessionContainer('lang');

        $years = $queryService->getActivityArchiveYears();
        $year = $this->params()->fromRoute('year');
        // If no year is supplied, use the latest year.
        if (is_null($year)) {
            $year = max($years);
        }

        $activities = $queryService->getFinishedActivitiesByYear($year);
        $translatedActivities = [];
        foreach ($activities as $activity){
            $translatedActivities[] = $translatorService->getTranslatedActivity($activity, $langSession->lang);
        }


        return new ViewModel([
            'activeYear' => $year,
            'years' => $years,
            'activities' => $translatedActivities
        ]);
    }
}
