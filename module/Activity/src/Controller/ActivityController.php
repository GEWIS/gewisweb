<?php

namespace Activity\Controller;

use Laminas\Session\AbstractContainer;
use Activity\Form\{
    ModifyRequest as RequestForm,
    Signup as SignupForm,
};
use Activity\Model\{
    Activity as ActivityModel,
    SignupList as SignupListModel,
};
use Activity\Service\{
    AclService,
    Activity as ActivityService,
    ActivityQuery as ActivityQueryService,
    Signup as SignupService,
    SignupListQuery as SignupListQueryService,
};
use DateTime;
use Laminas\Form\FormInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Session\Container as SessionContainer;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;

class ActivityController extends AbstractActionController
{
    /**
     * @var ActivityService
     */
    private ActivityService $activityService;

    /**
     * @var ActivityQueryService
     */
    private ActivityQueryService $activityQueryService;

    /**
     * @var SignupService
     */
    private SignupService $signupService;

    /**
     * @var SignupListQueryService
     */
    private SignupListQueryService $signupListQueryService;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * ActivityController constructor.
     *
     * @param ActivityService $activityService
     * @param ActivityQueryService $activityQueryService
     * @param SignupService $signupService
     * @param SignupListQueryService $signupListQueryService
     * @param AclService $aclService
     * @param Translator $translator
     */
    public function __construct(
        ActivityService $activityService,
        ActivityQueryService $activityQueryService,
        SignupService $signupService,
        SignupListQueryService $signupListQueryService,
        AclService $aclService,
        Translator $translator
    ) {
        $this->activityService = $activityService;
        $this->activityQueryService = $activityQueryService;
        $this->signupService = $signupService;
        $this->signupListQueryService = $signupListQueryService;
        $this->aclService = $aclService;
        $this->translator = $translator;
    }

    /**
     * View all activities.
     */
    public function indexAction()
    {
        $activities = $this->activityQueryService->getUpcomingActivities($this->params('category'));

        return new ViewModel(
            [
                'activities' => $activities,
                'category' => $this->params('category'),
            ]
        );
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $activityId = (int)$this->params('id');
        $activity = $this->activityQueryService->getActivity($activityId);

        if (null === $activity) {
            return $this->notFoundAction();
        }

        // If the Activity has a sign-up list always display it by redirecting the request.
        if (0 !== $activity->getSignupLists()->count()) {
            return $this->forward()->dispatch(
                ActivityController::class,
                [
                    'action' => 'viewSignupList',
                    'id' => $activityId,
                    'signupList' => $activity->getSignupLists()->first()->getId(),
                ]
            );
        }

        return new ViewModel(
            [
                'activity' => $activity,
            ]
        );
    }

    public function viewSignupListAction()
    {
        $activityId = (int)$this->params('id');
        $signupListId = (int)$this->params('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (null === $signupList) {
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

        $isSignedUp = false;
        // TODO: you are passing a signup list while an activity is expected (repeated multiple times)
        if ($this->signupService->isAllowedToInternalSubscribe()) {
            $identity = $this->aclService->getIdentityOrThrowException();
            $isSignedUp = $isAllowedToSubscribe
                && $this->signupService->isSignedUp($signupList, $identity);
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
                    ActivityModel::STATUS_APPROVED === $activity->getStatus(),
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
     * @param SignupListModel $signupList
     * @param SessionContainer $activitySession
     *
     * @return SignupForm|null $form
     */
    protected function prepareSignupForm(SignupListModel $signupList, SessionContainer $activitySession)
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

        if (null === $signupList) {
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

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            // Ensure the user is allowed to subscribe
            if (!$this->signupService->isAllowedToSubscribe()) {
                $error = $this->translator->translate('You need to log in to subscribe');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            // Ensure that the action is within the subscription window
            if (
                !$this->signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                || ActivityModel::STATUS_APPROVED !== $signupList->getActivity()->getStatus()
            ) {
                $error = $this->translator->translate('You cannot subscribe to this activity at this moment in time');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $identity = $this->aclService->getIdentityOrThrowException();

            // Check if the user is not already subscribed
            if ($this->signupService->isSignedUp($signupList, $identity)) {
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
     * $error message can be displayed if the request was unsuccessful (i.e.
     * $success was false).
     *
     * @param int $activityId
     * @param int $signupListId
     * @param bool $success Whether the request was successful
     * @param string $message
     * @param AbstractContainer|null $session
     *
     * @return Response
     */
    protected function redirectActivityRequest(
        int $activityId,
        int $signupListId,
        bool $success,
        string $message,
    ): Response {
        if ($success) {
            $this->flashMessenger()->addSuccessMessage($message);
        } else {
            $this->flashMessenger()->addErrorMessage($message);
        }

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

        if (null === $signupList) {
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

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            // Ensure the user is allowed to subscribe
            if (!$this->signupService->isAllowedToExternalSubscribe()) {
                $error = $this->translator->translate('You need to log in to subscribe');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            // Ensure that the action is within the subscription window
            if (
                !$this->signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                || ActivityModel::STATUS_APPROVED !== $signupList->getActivity()->getStatus()
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

        if (null === $signupList) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form = new RequestForm('activitysignoff');
            $form->setData($this->getRequest()->getPost());

            // Check if the form is valid
            if (!$form->isValid()) {
                $message = $this->translator->translate('Wrong form');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $message);
            }

            // Ensure the user is allowed to (UN)subscribe
            if (!$this->signupService->isAllowedToSubscribe()) {
                $message = $this->translator->translate('You have to be logged in to subscribe for this activity');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $message);
            }

            // Ensure that the action is within the subscription window
            if (
                !$this->signupService->isInSubscriptionWindow($signupList->getOpenDate(), $signupList->getCloseDate())
                || ActivityModel::STATUS_APPROVED !== $signupList->getActivity()->getStatus()
            ) {
                $error = $this->translator->translate('You cannot unsubscribe from this activity at this moment in time');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $identity = $this->aclService->getIdentityOrThrowException();

            // Check if the user is subscribed
            if (!$this->signupService->isSignedUp($signupList, $identity)) {
                $message = $this->translator->translate('You are not subscribed to this activity!');

                return $this->redirectActivityRequest($activityId, $signupListId, false, $message);
            }

            $this->signupService->signOff($signupList, $identity);
            $message = $this->translator->translate('Successfully unsubscribed');

            return $this->redirectActivityRequest($activityId, $signupListId, true, $message);
        }

        $error = $this->translator->translate('Use the form to unsubscribe');

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
        if (null === $year) {
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
