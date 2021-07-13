<?php

namespace Activity\Controller;

use Activity\Form\ModifyRequest as RequestForm;
use Activity\Model\Activity;
use Activity\Model\SignupList;
use Activity\Service\ActivityQuery;
use Activity\Service\Signup;
use Activity\Service\SignupListQuery;
use DateTime;
use Decision\Service\Member;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Session\Container as SessionContainer;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use User\Service\User;

class ActivityController extends AbstractActionController
{
    private Translator $translator;

    /**
     * @var \Activity\Service\Activity
     */
    private $activityService;

    /**
     * @var ActivityQuery
     */
    private $activityQueryService;

    /**
     * @var Signup
     */
    private $signupService;

    /**
     * @var SignupListQuery
     */
    private $signupListQueryService;

    private User $userService;

    public function __construct(Translator $translator, \Activity\Service\Activity $activityService, ActivityQuery $activityQueryService, Signup $signupService, SignupListQuery $signupListQueryService, User $userService)
    {
        $this->activityService = $activityService;
        $this->activityQueryService = $activityQueryService;
        $this->signupService = $signupService;
        $this->signupListQueryService = $signupListQueryService;
        $this->translator = $translator;
        $this->userService = $userService;
    }

    /**
     * View all activities.
     */
    public function indexAction()
    {
        $activities = $this->activityQueryService->getUpcomingActivities($this->params('category'));

        return ['activities' => $activities, 'category' => $this->params('category')];
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $activityId = (int)$this->params('id');
        $activity = $this->activityQueryService->getActivity($activityId);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        // If the Activity has a sign-up list always display it by redirecting the request.
        if (0 !== $activity->getSignupLists()->count()) {
            return $this->forward()->dispatch(
                'Activity\Controller\Activity',
                [
                    'action' => 'viewSignupList',
                    'id' => $activityId,
                    'signupList' => $activity->getSignupLists()->first()->getId(),
                ]
            );
        }

        return [
            'activity' => $activity,
        ];
    }

    public function viewSignupListAction()
    {
        $activityId = (int)$this->params('id');
        $signupListId = (int)$this->params('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

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

        $isAllowedToSubscribe = $this->signupService->isAllowedToSubscribe();

        $activitySession = new SessionContainer('activityRequest');

        $fields = $signupList->getFields();
        $form = $this->prepareSignupForm($signupList, $activitySession);

        $identity = $this->userService->getIdentity();
        $isSignedUp = false;
        if ($this->signupService->isAllowedToInternalSubscribe()) {
            $isSignedUp = $isAllowedToSubscribe
                && $this->signupService->isSignedUp($signupList, $identity->getMember());
        }

        $subscriptionOpenDatePassed = $signupList->getOpenDate() < new DateTime();
        $subscriptionCloseDatePassed = $signupList->getCloseDate() < new DateTime();
        $isArchived = $activity->getEndTime() < new DateTime();

        $view = new ViewModel(
            [
                'activity' => $activity,
                'signupLists' => $signupLists,
                'signupList' => $signupList,
                'isArchived' => $isArchived,
                'signupOpen' => $subscriptionOpenDatePassed &&
                    !$subscriptionCloseDatePassed &&
                    Activity::STATUS_APPROVED === $activity->getStatus(),
                'isAllowedToSubscribe' => $isAllowedToSubscribe,
                'isSignedUp' => $isSignedUp,
                'signupData' => $this->signupService->isAllowedToViewSubscriptions() ?
                    $this->signupService->getSignedUpData($signupList) :
                    null,
                'form' => $form,
                'signoffForm' => new RequestForm('activitysignoff', 'Unsubscribe'),
                'fields' => $fields,
                'memberSignups' => $this->signupService->getNumberOfSubscribedMembers($signupList),
                'subscriptionOpenDatePassed' => $subscriptionOpenDatePassed,
                'subscriptionCloseDatePassed' => $subscriptionCloseDatePassed,
            ]
        );
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
     * @param SignupList $fields
     * @param SessionContainer $activitySession
     *
     * @return \Activity\Form\Signup $form
     */
    protected function prepareSignupForm($signupList, &$activitySession)
    {
        if ($this->signupService->isAllowedToSubscribe()) {
            $form = $this->signupService->getForm($signupList);

            if (isset($activitySession->signupData)) {
                $form->setData(new Parameters($activitySession->signupData));
                $form->isValid();
                unset($activitySession->signupData);
            }

            return $form;
        }

        if ($this->signupService->isAllowedToExternalSubscribe()) {
            $form = $this->signupService->getExternalForm($signupList);

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
        $form = $this->activityService->getActivityForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->activityService->createActivity($request->getPost())) {
                $view = new ViewModel();
                $view->setTemplate('activity/activity/createSuccess.phtml');

                return $view;
            }
        }

        return [
            'form' => $form,
            'action' => $this->translator->translate('Create Activity'),
            'allowSignupList' => true,
        ];
    }

    /**
     * Signup for a activity.
     */
    public function signupAction()
    {
        $activityId = (int)$this->params('id');
        $signupListId = (int)$this->params('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form = $this->signupService->getForm($signupList);
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $this->translator->translate('Invalid form');
                $activityRequestSession = new SessionContainer('activityRequest');
                $activityRequestSession->signupData = $postData->toArray();

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error, $activityRequestSession);
            }

            // Ensure the user is allowed to subscribe
            if (!$this->signupService->isAllowedToSubscribe()) {
                $error = $this->translator->translate('You need to log in to subscribe');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            // Ensure that the action is within the subscription window
            if (
                !$this->signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                || Activity::STATUS_APPROVED !== $signupList->getActivity()->getStatus()
            ) {
                $error = $this->translator->translate('You cannot subscribe to this activity at this moment in time');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $identity = $this->userService->getIdentity();
            $user = $identity->getMember();

            // Check if the user is not already subscribed
            if ($this->signupService->isSignedUp($signupList, $user)) {
                $error = $this->translator->translate('You have already been subscribed for this activity');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $this->signupService->signUp($signupList, $form->getData(FormInterface::VALUES_AS_ARRAY));
            $message = $this->translator->translate('Successfully subscribed');

            return $this->redirectActivityRequest($activityId, $signupListId, true, $message);
        }

        $error = $this->translator->translate('Use the form to subscribe');

        return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
    }

    /**
     * Redirects to the view of the activity with the given $id, where the
     * $error message can be displayed if the request was unsuccesful (i.e.
     * $success was false).
     *
     * @param int $id
     * @param bool $success Whether the request was successful
     * @param string $message
     */
    protected function redirectActivityRequest($activityId, $signupListId, $success, $message, $session = null)
    {
        if (is_null($session)) {
            $session = new SessionContainer('activityRequest');
        }

        $session->success = $success;
        $session->message = $message;

        return $this->redirect()->toRoute(
            'activity/view/signuplist',
            [
                'id' => $activityId,
                'signupList' => $signupListId,
            ]
        );
    }

    public function externalSignupAction()
    {
        $activityId = (int)$this->params('id');
        $signupListId = (int)$this->params('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form = $this->signupService->getExternalForm($signupList);
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $this->translator->translate('Invalid form');
                $activityRequestSession = new SessionContainer('activityRequest');
                $activityRequestSession->signupData = $postData->toArray();

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error, $activityRequestSession);
            }

            // Ensure the user is allowed to subscribe
            if (!$this->signupService->isAllowedToExternalSubscribe()) {
                $error = $this->translator->translate('You need to log in to subscribe');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            // Ensure that the action is within the subscription window
            if (
                !$this->signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                || Activity::STATUS_APPROVED !== $signupList->getActivity()->getStatus()
            ) {
                $error = $this->translator->translate('You cannot subscribe to this activity at this moment in time');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);
            $fullName = $formData['fullName'];
            unset($formData['fullName']);
            $email = $formData['email'];
            unset($formData['email']);
            $this->signupService->externalSignUp($signupList, $fullName, $email, $formData);
            $message = $this->translator->translate('Successfully subscribed as external participant');

            return $this->redirectActivityRequest($activityId, $signupListId, true, $message);
        }

        $error = $this->translator->translate('Use the form to subscribe');

        return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
    }

    /**
     * Signup for a activity.
     */
    public function signoffAction()
    {
        $activityId = (int)$this->params('id');
        $signupListId = (int)$this->params('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (is_null($signupList)) {
            return $this->notFoundAction();
        }

        $translator = $this->getServiceLocator()->get('translator');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form = new RequestForm('activitysignoff');
            $form->setData($this->getRequest()->getPost());

            // Check if the form is valid
            if (!$form->isValid()) {
                $message = $translator->translate('Wrong form');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $message);
            }

            // Ensure the user is allowed to (UN)subscribe
            if (!$this->signupService->isAllowedToSubscribe()) {
                $message = $translator->translate('You have to be logged in to subscribe for this activity');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $message);
            }

            // Ensure that the action is within the subscription window
            if (
                !$this->signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                || Activity::STATUS_APPROVED !== $signupList->getActivity()->getStatus()
            ) {
                $error = $translator->translate('You cannot unsubscribe from this activity at this moment in time');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $identity = $this->userService->getIdentity();
            $user = $identity->getMember();

            // Check if the user is subscribed
            if (!$this->signupService->isSignedUp($signupList, $user)) {
                $message = $translator->translate('You are not subscribed to this activity!');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $message);
            }

            $this->signupService->signOff($signupList, $user);
            $message = $translator->translate('Successfully unsubscribed');

            return $this->redirectActivityRequest($activityId, $signupListId, true, $message);
        }

        $error = $translator->translate('Use the form to unsubscribe');

        return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
    }

    /**
     * Display all the finished activities in a school year.
     *
     * @return ViewModel
     */
    public function archiveAction()
    {
        $years = $this->activityQueryService->getActivityArchiveYears();
        $year = $this->params()->fromRoute('year');

        // If no year is supplied, use the latest year.
        if (is_null($year)) {
            $year = max($years);
        }

        return new ViewModel(
            [
                'activeYear' => $year,
                'years' => $years,
                'activities' => $this->activityQueryService->getFinishedActivitiesByYear($year),
            ]
        );
    }
}
