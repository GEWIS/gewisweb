<?php

declare(strict_types=1);

namespace Activity\Controller;

use Activity\Form\Signup as SignupForm;
use Activity\Mapper\Signup as SignupMapper;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\SignupList as SignupListModel;
use Activity\Service\AclService;
use Activity\Service\ActivityQuery as ActivityQueryService;
use Activity\Service\Signup as SignupService;
use Activity\Service\SignupListQuery as SignupListQueryService;
use Application\Form\ModifyRequest as RequestForm;
use DateTime;
use Laminas\Form\FormInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Session\Container as SessionContainer;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\ParametersInterface;
use Laminas\View\Model\ViewModel;

use function count;
use function date;
use function max;

/**
 * @method FlashMessenger flashMessenger()
 */
class ActivityController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly SignupMapper $signupMapper,
        private readonly ActivityQueryService $activityQueryService,
        private readonly SignupService $signupService,
        private readonly SignupListQueryService $signupListQueryService,
    ) {
    }

    /**
     * View all activities.
     */
    public function indexAction(): ViewModel
    {
        $category = $this->params()->fromRoute('category');
        $activities = $this->activityQueryService->getUpcomingActivities($category);

        return new ViewModel(
            [
                'activities' => $activities,
                'category' => $category,
            ],
        );
    }

    /**
     * View one activity.
     */
    public function viewAction(): mixed
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $activity = $this->activityQueryService->getActivity($activityId);

        if (null === $activity) {
            return $this->notFoundAction();
        }

        if (ActivityModel::STATUS_APPROVED !== $activity->getStatus()) {
            if (!$this->aclService->isAllowed('update', $activity)) {
                return $this->notFoundAction();
            }
        }

        // If the Activity has a sign-up list always display it by redirecting the request.
        if (!$activity->getSignupLists()->isEmpty()) {
            return $this->forward()->dispatch(
                self::class,
                [
                    'action' => 'viewSignupList',
                    'id' => $activityId,
                    'signupList' => $activity->getSignupLists()->first()->getId(),
                ],
            );
        }

        return new ViewModel(
            [
                'activity' => $activity,
            ],
        );
    }

    public function viewSignupListAction(): ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $signupListId = (int) $this->params()->fromRoute('signupList');
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
        if ($this->signupService->isAllowedToInternalSubscribe()) {
            $identity = $this->aclService->getUserIdentityOrThrowException();
            $isSignedUp = $isAllowedToSubscribe
                && $this->signupService->isSignedUp($signupList, $identity);
            if ($isSignedUp) {
                if (null !== ($signup = $this->signupMapper->getSignUp($signupList, $identity))) {
                    $form->setData($signup->toFormArray());
                }
            }
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
            ],
        );
        $view->setTemplate('activity/activity/view.phtml');

        // Retrieve and clear the request status from the session, if it exists.
        if (isset($activitySession->reopen)) {
            $view->setVariable('reopen', $activitySession->reopen);
            unset($activitySession->reopen);
        }

        return $view;
    }

    /**
     * Get the appropriate signup form.
     *
     * @return SignupForm|null $form
     */
    protected function prepareSignupForm(
        SignupListModel $signupList,
        SessionContainer $activitySession,
    ): ?SignupForm {
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
     * Signup for an activity.
     */
    public function signupAction(): Response|ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $signupListId = (int) $this->params()->fromRoute('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (null === $signupList) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $activityRequestSession = new SessionContainer('activityRequest');
        $activityRequestSession->reopen = false;

        if ($request->isPost()) {
            $form = $this->signupService->getForm($signupList);
            /** @var ParametersInterface $postData */
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $this->translator->translate('Invalid form');
                $activityRequestSession->reopen = true;
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

            $identity = $this->aclService->getUserIdentityOrThrowException();

            // Let user edit subscription details
            if (null !== ($signup = $this->signupMapper->getSignUp($signupList, $identity))) {
                if ($signupList->getFields()->isEmpty()) {
                    return $this->redirect()->toRoute(
                        'activity/view/signuplist',
                        [
                            'id' => $activityId,
                            'signupList' => $signupListId,
                        ],
                    );
                }

                $this->signupService->editSignUp($signup, $form->getData(FormInterface::VALUES_AS_ARRAY));
                $message = $this->translator->translate('Successfully updated subscription!');
            } else {
                $this->signupService->signUp($signupList, $form->getData(FormInterface::VALUES_AS_ARRAY));
                $message = $this->translator->translate('Successfully subscribed');
            }

            return $this->redirectActivityRequest($activityId, $signupListId, true, $message);
        }

        $error = $this->translator->translate('Use the form to subscribe');
        $activityRequestSession->reopen = true;

        return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
    }

    /**
     * Redirects to the view of the activity with the given $id, where the
     * $error message can be displayed if the request was unsuccessful (i.e.
     * $success was false).
     *
     * @param bool $success Whether the request was successful
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
            ],
        );
    }

    public function externalSignupAction(): Response|ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $signupListId = (int) $this->params()->fromRoute('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (null === $signupList) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $activityRequestSession = new SessionContainer('activityRequest');
        $activityRequestSession->reopen = false;

        if ($request->isPost()) {
            $form = $this->signupService->getExternalForm($signupList);
            /** @var ParametersInterface $postData */
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $this->translator->translate('Invalid form');
                $activityRequestSession->reopen = true;
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
        $activityRequestSession->reopen = true;

        return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
    }

    /**
     * Signup for an activity.
     */
    public function signoffAction(): Response|ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $signupListId = (int) $this->params()->fromRoute('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (null === $signupList) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form = new RequestForm('activitysignoff');
            $form->setData($request->getPost());

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
                $error = $this->translator->translate(
                    'You cannot unsubscribe from this activity at this moment in time',
                );

                return $this->redirectActivityRequest($activityId, $signupListId, false, $error);
            }

            $identity = $this->aclService->getUserIdentityOrThrowException();

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
     */
    public function archiveAction(): ViewModel
    {
        $years = $this->activityQueryService->getActivityArchiveYears();
        $year = $this->params()->fromRoute('year');

        // If no year is supplied, use the latest year.
        if (null === $year) {
            if (0 === count($years)) {
                $year = (int) date('Y');
            } else {
                $year = max($years);
            }
        } else {
            $year = (int) $year;
        }

        return new ViewModel(
            [
                'years' => $years,
                'year' => $year,
                'activities' => $this->activityQueryService->getFinishedActivitiesByYear($year),
            ],
        );
    }
}
