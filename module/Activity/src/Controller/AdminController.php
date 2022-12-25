<?php

namespace Activity\Controller;

use Activity\Mapper\Signup as SignupMapper;
use Activity\Model\Activity as ActivityModel;
use Activity\Service\{
    AclService,
    Activity as ActivityService,
    ActivityQuery as ActivityQueryService,
    Signup as SignupService,
    SignupListQuery as SignupListQueryService,
};
use Application\Form\ModifyRequest as RequestForm;
use DateTime;
use Laminas\Form\FormInterface;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Paginator\Paginator;
use Laminas\Session\{
    AbstractContainer,
    Container as SessionContainer,
};
use Laminas\Stdlib\{
    Parameters,
    ParametersInterface,
};
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * Controller that gives some additional details for activities, such as a list of email adresses
 * or an export function specially tailored for the organizer.
 */
class AdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityService $activityService,
        private readonly ActivityQueryService $activityQueryService,
        private readonly SignupService $signupService,
        private readonly SignupListQueryService $signupListQueryService,
        private readonly SignupMapper $signupMapper,
    ) {
    }

    public function updateAction(): Response|ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $activity = $this->activityQueryService->getActivityWithDetails($activityId);

        if (null === $activity) {
            return $this->notFoundAction();
        }

        if (!$this->aclService->isAllowed('update', $activity)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to update this activity'));
        }

        if (0 !== $activity->getSignupLists()->count()) {
            $openingDates = [];
            $participants = 0;

            foreach ($activity->getSignupLists() as $signupList) {
                $openingDates[] = $signupList->getOpenDate();
                $participants += $signupList->getSignups()->count();
            }

            if (min($openingDates) < new DateTime() || ActivityModel::STATUS_APPROVED === $activity->getStatus()) {
                $message = $this->translator->translate(
                    'Activities that have sign-up lists which are open or approved cannot be updated.'
                );

                return $this->redirectActivityAdmin(false, $message);
            }
        }

        // Can also be `elseif` as SignupLists are guaranteed to be before the
        // Activity begin date and time.
        if ($activity->getBeginTime() < new DateTime()) {
            $message = $this->translator->translate(
                'This activity has already started/ended and can no longer be updated.'
            );

            return $this->redirectActivityAdmin(false, $message);
        }

        $form = $this->activityService->getActivityForm();
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->activityService->createUpdateProposal($activity, $form->getData())) {
                    return $this->redirectActivityAdmin(
                        true,
                        // phpcs:ignore -- user-visible strings should not be split
                        $this->translator->translate('You have successfully created an update proposal for the activity! If the activity was already approved, the proposal will be applied after it has been approved by the board. Otherwise, the update has already been applied to the activity.'),
                    );
                }
            }
        }

        $updateProposal = $activity->getUpdateProposal();

        if (0 !== $updateProposal->count()) {
            // If there already is an update proposal for this activity, show that instead of the original activity.
            $activity = $updateProposal->first()->getNew();
        }

        $activityData = $activity->toArray();
        unset($activityData['id']);

        $languages = $this->activityQueryService->getAvailableLanguages($activity);
        $activityData['language_dutch'] = $languages['nl'];
        $activityData['language_english'] = $languages['en'];

        $activityData['organ'] = $activity->getOrgan()?->getId();
        $activityData['company'] = $activity->getCompany()?->getId();

        $allowSignupList = true;
        if (
            ActivityModel::STATUS_APPROVED === $activity->getStatus(
            ) || (isset($participants) && 0 !== $participants)
        ) {
            $allowSignupList = false;
            unset($activityData['signupLists']);
        }

        $form->setData($activityData);

        $viewModel = new ViewModel(
            [
                'form' => $form,
                'action' => $this->translator->translate('Update Activity'),
                'allowSignupList' => $allowSignupList,
            ]
        );
        $viewModel->setTemplate('activity/activity/create.phtml');

        return $viewModel;
    }

    /**
     * @param bool $success
     * @param string $message
     *
     * @return Response
     */
    protected function redirectActivityAdmin(
        bool $success,
        string $message,
    ): Response {
        if ($success) {
            $this->plugin('FlashMessenger')->addSuccessMessage($message);
        } else {
            $this->plugin('FlashMessenger')->addErrorMessage($message);
        }

        return $this->redirect()->toRoute('activity_admin');
    }

    /**
     * Return the data of the activity participants.
     *
     * @return ViewModel
     */
    public function participantsAction(): ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $signupListId = (int) $this->params()->fromRoute('signupList');

        if (0 === $signupListId) {
            $activity = $this->activityQueryService->getActivity($activityId);

            if (null === $activity) {
                return $this->notFoundAction();
            }

            // If the activity does not have any sign-up lists there is no need
            // to check the participants or any sign-up lists.
            if (0 === $activity->getSignupLists()->count()) {
                return $this->notFoundAction();
            }

            if (!$this->aclService->isAllowed('viewParticipants', $activity)) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to view the participants of this activity')
                );
            }
        } else {
            $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

            if (null === $signupList) {
                return $this->notFoundAction();
            }

            if (!$this->aclService->isAllowed('viewParticipants', $signupList)) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to view the participants of this activity')
                );
            }

            $activity = $this->activityQueryService->getActivity($activityId);
        }

        $result = [
            'activity' => $activity,
        ];

        if (isset($signupList)) {
            $result['signupList'] = $signupList;
            $activityAdminSession = new SessionContainer('activityAdminRequest');
            $externalSignupForm = $this->signupService->getExternalAdminForm($signupList);

            if (isset($activityAdminSession->signupData)) {
                $externalSignupForm->setData(new Parameters($activityAdminSession->signupData));
                $externalSignupForm->isValid();
                unset($activityAdminSession->signupData);
            }

            $result['externalSignupForm'] = $externalSignupForm;
            $result['externalSignoffForm'] = new RequestForm(
                'activityExternalSignoff',
                $this->translator->translate('Remove')
            );
        }

        $signupLists = [];

        foreach ($activity->getSignupLists()->getValues() as $signupList) {
            $signupLists[] = [
                'id' => $signupList->getId(),
                'name' => $signupList->getName(),
            ];
        }

        $result['signupLists'] = $signupLists;

        // Retrieve and clear the request status from the session, if it exists.
        if (isset($activityAdminSession->success)) {
            $result['success'] = $activityAdminSession->success;
            unset($activityAdminSession->success);
            $result['message'] = $activityAdminSession->message;
            unset($activityAdminSession->message);
        }

        return new ViewModel($result);
    }

    public function externalSignupAction(): Response|ViewModel
    {
        $activityId = (int) $this->params()->fromRoute('id');
        $signupListId = (int) $this->params()->fromRoute('signupList');
        $signupList = $this->signupListQueryService->getSignupListByActivity($signupListId, $activityId);

        if (null === $signupList) {
            return $this->notFoundAction();
        }

        if (!$this->aclService->isAllowed('adminSignup', $signupList)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to use this form'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form = $this->signupService->getExternalAdminForm($signupList);
            /** @var ParametersInterface $postData */
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $activityAdminSession = new SessionContainer('activityAdminRequest');
                $activityAdminSession->signupData = $postData->toArray();

                return $this->redirectActivityAdminRequest(
                    $activityId,
                    $signupListId,
                    false,
                    $this->translator->translate('Invalid form'),
                    $activityAdminSession,
                );
            }

            $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);
            $fullName = $formData['fullName'];
            unset($formData['fullName']);
            $email = $formData['email'];
            unset($formData['email']);
            $this->signupService->adminSignUp($signupList, $fullName, $email, $formData);

            return $this->redirectActivityAdminRequest(
                $activityId,
                $signupListId,
                true,
                $this->translator->translate('Successfully subscribed external participant'),
            );
        }

        return $this->redirectActivityAdminRequest(
            $activityId,
            $signupListId,
            false,
            $this->translator->translate('Use the form to subscribe'),
        );
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
    protected function redirectActivityAdminRequest(
        int $activityId,
        int $signupListId,
        bool $success,
        string $message,
        AbstractContainer $session = null,
    ): Response {
        if (null === $session) {
            $session = new SessionContainer('activityAdminRequest');
        }

        $session->success = $success;
        $session->message = $message;

        return $this->redirect()->toRoute(
            'activity_admin/participants',
            [
                'id' => $activityId,
                'signupList' => $signupListId,
            ]
        );
    }

    public function externalSignoffAction(): Response|ViewModel
    {
        $signupId = (int) $this->params()->fromRoute('id');
        $signup = $this->signupMapper->getExternalSignUp($signupId);

        if (null === $signup) {
            return $this->notFoundAction();
        }

        $signupList = $signup->getSignupList();

        if (!$this->aclService->isAllowed('adminSignup', $signupList)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to use this form'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        //Assure a form is used
        if ($request->isPost()) {
            $form = new RequestForm('activityExternalSignoff', $this->translator->translate('Remove'));
            $form->setData($request->getPost());

            //Assure the form is valid
            if (!$form->isValid()) {
                return $this->redirectActivityAdminRequest(
                    $signupList->getActivity()->getId(),
                    $signupList->getId(),
                    false,
                    $this->translator->translate('Invalid form'),
                );
            }

            $this->signupService->externalSignOff($signup);

            return $this->redirectActivityAdminRequest(
                $signupList->getActivity()->getId(),
                $signupList->getId(),
                true,
                $this->translator->translate('Successfully removed external participant'),
            );
        }

        return $this->redirectActivityAdminRequest(
            $signupList->getActivity()->getId(),
            $signupList->getId(),
            false,
            $this->translator->translate('Use the form to unsubscribe an external participant'),
        );
    }

    /**
     * Show a list of all activities this user can manage.
     */
    public function viewAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAdmin', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer activities'));
        }

        $admin = false;
        $disapprovedActivities = null;
        $unapprovedActivities = null;
        $approvedActivities = null;

        if ($this->aclService->isAllowed('approval', 'activity')) {
            $admin = true;
            $disapprovedActivities = $this->activityQueryService->getDisapprovedActivities();
            $unapprovedActivities = $this->activityQueryService->getUnapprovedActivities();
            $approvedActivities = $this->activityQueryService->getApprovedActivities();
        }

        $identity = $this->aclService->getUserIdentityOrThrowException();
        $paginator = new Paginator($this->activityQueryService->getOldCreatedActivitiesPaginator($identity));
        $paginator->setDefaultItemCountPerPage(15);
        $page = $this->params()->fromRoute('page');

        if ($page && 0 !== $paginator->count()) {
            $paginator->setCurrentPageNumber($paginator->normalizePageNumber($page));
        }

        $result = [
            'upcomingActivities' => $this->activityQueryService->getUpcomingCreatedActivities($identity),
            'disapprovedActivities' => $disapprovedActivities,
            'unapprovedActivities' => $unapprovedActivities,
            'approvedActivities' => $approvedActivities,
            'oldActivityPaginator' => $paginator,
            'admin' => $admin,
        ];

        $activityAdminSession = new SessionContainer('activityAdmin');
        if (isset($activityAdminSession->success)) {
            $result['success'] = $activityAdminSession->success;
            unset($activityAdminSession->success);
            $result['message'] = $activityAdminSession->message;
            unset($activityAdminSession->message);
        }

        return new ViewModel($result);
    }
}
