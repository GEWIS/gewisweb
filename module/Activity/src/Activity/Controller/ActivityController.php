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
        $activities = $queryService->getUpcomingActivities($this->params('category'));
        return ['activities' => $activities, 'category' => $this->params('category')];
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $activityId = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $activity = $queryService->getActivity($activityId);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        $signupLists = null;
        if ($activity->getSignupLists()->count() > 0) {
            $signupLists = [];

            foreach ($activity->getSignupLists()->getValues() as $signupList) {
                $signupLists[] = [
                    'id' => $signupList->getId(),
                    'name' => $signupList->getName(),
                ];
            }
        }

        $result = [
            'activity' => $activity,
            'signupLists' => $signupLists,
        ];

        return $result;
    }

    public function viewSignupListAction()
    {
        $activityId = (int) $this->params('id');
        $signupListId = (int) $this->params('signupList');
        $signupListQueryService = $this->getServiceLocator()->get('activity_service_signupListQuery');
        $signupList = $signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $activity = $signupList->getActivity();

        $signupLists = [];
        foreach ($activity->getSignupLists()->getValues() as $list) {
            $signupLists[] = [
                'id' => $list->getId(),
                'name' => $list->getName(),
            ];
        }

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $isAllowedToSubscribe = $signupService->isAllowedToSubscribe();

        $activitySession = new SessionContainer('activityRequest');

        $fields = $signupList->getFields();
        $form = $this->prepareSignupForm($signupList, $activitySession);

        $identity = $this->getServiceLocator()->get('user_role');
        $isSignedUp = false;
        if ($signupService->isAllowedToInternalSubscribe()) {
            $isSignedUp = $isAllowedToSubscribe
                && $signupService->isSignedUp($signupList, $identity->getMember());
        }

        $subscriptionOpenDatePassed = $signupList->getOpenDate() < new \DateTime();
        $subscriptionCloseDatePassed = $signupList->getCloseDate() < new \DateTime();
        $isArchived = $activity->getEndTime() < new \DateTime();

        $view = new ViewModel([
            'activity' => $activity,
            'signupLists' => $signupLists,
            'signupList' => $signupList,
            'isArchived' => $isArchived,
            'signupOpen' => $subscriptionOpenDatePassed &&
                !$subscriptionCloseDatePassed &&
                $activity->getStatus() === Activity::STATUS_APPROVED,
            'isAllowedToSubscribe' => $isAllowedToSubscribe,
            'isSignedUp' => $isSignedUp,
            'signupData' => $signupService->isAllowedToViewSubscriptions() ?
                $signupService->getSignedUpData($signupList) :
                null,
            'form' => $form,
            'signoffForm' => new RequestForm('activitysignoff', 'Unsubscribe'),
            'fields' => $fields,
            'memberSignups' => $signupService->getNumberOfSubscribedMembers($signupList),
            'subscriptionOpenDatePassed' => $subscriptionOpenDatePassed,
            'subscriptionCloseDatePassed' => $subscriptionCloseDatePassed,
        ]);
        $view->setTemplate('activity/activity/view.phtml');

        // Retrieve and clear the request status from the session, if it exists.
        if (isset($activitySession->success)) {
            $view->setVariable('success', $activitySession->success);
            unset($activitySession->success);
            $view->setVariable('message', $activitySession->message);
            unset($activitySession->message);
        }

        return $view;
    }

    /**
     * Get the appropriate signup form.
     *
     * @param type $fields
     * @param type $activitySession
     * @return type $form
     */
    protected function prepareSignupForm($signupList, & $activitySession)
    {
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        if ($signupService->isAllowedToSubscribe()) {
            $form = $signupService->getForm($signupList);
            if (isset($activitySession->signupData)) {
                $form->setData(new Parameters($activitySession->signupData));
                $form->isValid();
                unset($activitySession->signupData);
            }
            return $form;
        }
        if ($signupService->isAllowedToExternalSubscribe()) {
            $form = $signupService->getExternalForm($signupList);
            if (isset($activitySession->signupData)) {
                $form->setData(new Parameters($activitySession->signupData));
                $form->isValid();
                unset($activitySession->signupData);
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
        $form = $activityService->getActivityForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($activityService->createActivity($request->getPost())) {
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
        $activityId = (int) $this->params('id');
        $signupListId = (int) $this->params('signupList');
        $signupListQueryService = $this->getServiceLocator()->get('activity_service_signupListQuery');
        $signupList = $signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $translator = $this->getServiceLocator()->get('translator');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $signupService = $this->getServiceLocator()->get('activity_service_signup');

            $form = $signupService->getForm($signupList);
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $translator->translate('Invalid form');
                $activityRequestSession = new SessionContainer('activityRequest');
                $activityRequestSession->signupData = $postData->toArray();
                $this->redirectActivityRequest($activityId, $signupListId, false, $error, $activityRequestSession);
                return $this->getResponse();
            }

            // Ensure the user is allowed to subscribe
            if (!$signupService->isAllowedToSubscribe()) {
                $error = $translator->translate('You need to log in to subscribe');
                $this->redirectActivityRequest($activityId, $signupListId, false, $error);
                return $this->getResponse();
            }

            // Ensure that the action is within the subscription window
            if (!$signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                    || $signupList->getActivity()->getStatus() !== Activity::STATUS_APPROVED) {
                $error = $translator->translate('You cannot subscribe to this activity at this moment in time');
                $this->redirectActivityRequest($activityId, $signupListId, false, $error);
                return $this->getResponse();
            }

            $identity = $this->getServiceLocator()->get('user_service_user')->getIdentity();
            $user = $identity->getMember();

            // Check if the user is not already subscribed
            if ($signupService->isSignedUp($signupList, $user)) {
                $error = $translator->translate('You have already been subscribed for this activity');
                $this->redirectActivityRequest($activityId, $signupListId, false, $error);
                return $this->getResponse();
            }

            $signupService->signUp($signupList, $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY));
            $message = $translator->translate('Successfully subscribed');
            $this->redirectActivityRequest($activityId, $signupListId, true, $message);
            return $this->getResponse();
        }

        $error = $translator->translate('Use the form to subscribe');
        $this->redirectActivityRequest($activityId, $signupListId, false, $error);
        return $this->getResponse();
    }

    public function externalSignupAction()
    {
        $activityId = (int) $this->params('id');
        $signupListId = (int) $this->params('signupList');
        $signupListQueryService = $this->getServiceLocator()->get('activity_service_signupListQuery');
        $signupList = $signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $translator = $this->getServiceLocator()->get('translator');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $signupService = $this->getServiceLocator()->get('activity_service_signup');

            $form = $signupService->getExternalForm($signupList);
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $translator->translate('Invalid form');
                $activityRequestSession = new SessionContainer('activityRequest');
                $activityRequestSession->signupData = $postData->toArray();
                $this->redirectActivityRequest($activityId, $signupListId, false, $error, $activityRequestSession);
                return $this->getResponse();
            }

            // Ensure the user is allowed to subscribe
            if (!$signupService->isAllowedToExternalSubscribe()) {
                $error = $translator->translate('You need to log in to subscribe');
                $this->redirectActivityRequest($activityId, $signupListId, false, $error);
                return $this->getResponse();
            }

            // Ensure that the action is within the subscription window
            if (!$signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                    || $signupList->getActivity()->getStatus() !== Activity::STATUS_APPROVED) {
                $error = $translator->translate('You cannot subscribe to this activity at this moment in time');
                $this->redirectActivityRequest($activityId, $signupListId, false, $error);
                return $this->getResponse();
            }

            $formData = $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY);
            $fullName = $formData['fullName'];
            unset($formData['fullName']);
            $email = $formData['email'];
            unset($formData['email']);
            $signupService->externalSignUp($signupList, $fullName, $email, $formData);
            $message = $translator->translate('Successfully subscribed as external participant');
            $this->redirectActivityRequest($activityId, $signupListId, true, $message);
            return $this->getResponse();
        }

        $error = $translator->translate('Use the form to subscribe');
        $this->redirectActivityRequest($activityId, $signupListId, false, $error);
        return $this->getResponse();
    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {
        $activityId = (int) $this->params('id');
        $signupListId = (int) $this->params('signupList');
        $signupListQueryService = $this->getServiceLocator()->get('activity_service_signupListQuery');
        $signupList = $signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $translator = $this->getServiceLocator()->get('translator');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $signupService = $this->getServiceLocator()->get('activity_service_signup');

            $form = new RequestForm('activitysignoff');
            $form->setData($this->getRequest()->getPost());

            // Check if the form is valid
            if (!$form->isValid()){
                $message = $translator->translate('Wrong form');
                $this->redirectActivityRequest($activityId, $signupListId, false, $message);
                return $this->getResponse();
            }

            // Ensure the user is allowed to (UN)subscribe
            if (!$signupService->isAllowedToSubscribe()) {
                $message = $translator->translate('You have to be logged in to subscribe for this activity');
                $this->redirectActivityRequest($activityId, $signupListId, false, $message);
                return $this->getResponse();
            }

            // Ensure that the action is within the subscription window
            if (!$signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                    || $signupList->getActivity()->getStatus() !== Activity::STATUS_APPROVED) {
                $error = $translator->translate('You cannot unsubscribe from this activity at this moment in time');
                $this->redirectActivityRequest($activityId, $signupListId, false, $error);
                return $this->getResponse();
            }

            $identity = $this->getServiceLocator()->get('user_service_user')->getIdentity();
            $user = $identity->getMember();

            // Check if the user is subscribed
            if (!$signupService->isSignedUp($signupList, $user)) {
                $message = $translator->translate('You are not subscribed to this activity!');
                $this->redirectActivityRequest($activityId, $signupListId, false, $message);
                return $this->getResponse();
            }

            $signupService->signOff($signupList, $user);
            $message = $translator->translate('Successfully unsubscribed');
            $this->redirectActivityRequest($activityId, $signupListId, true, $message);
            return $this->getResponse();
        }

        $error = $translator->translate('Use the form to unsubscribe');
        $this->redirectActivityRequest($activityId, $signupListId, false, $error);
        return $this->getResponse();
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
    protected function redirectActivityRequest($activityId, $signupListId, $success, $message, $session = null)
    {
        if (is_null($session)) {
            $session = new SessionContainer('activityRequest');
        }
        $session->success = $success;
        $session->message = $message;
        $this->redirect()->toRoute('activity/view/signuplist', [
            'id' => $activityId,
            'signupList' => $signupListId,
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

        $years = $queryService->getActivityArchiveYears();
        $year = $this->params()->fromRoute('year');
        // If no year is supplied, use the latest year.
        if (is_null($year)) {
            $year = max($years);
        }
        return new ViewModel([
            'activeYear' => $year,
            'years' => $years,
            'activities' => $queryService->getFinishedActivitiesByYear($year)
        ]);
    }
}
