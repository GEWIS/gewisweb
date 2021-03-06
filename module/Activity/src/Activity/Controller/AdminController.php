<?php

namespace Activity\Controller;

use Activity\Model\Activity;
use Activity\Service\Signup;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Activity\Form\ModifyRequest as RequestForm;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use DOMPDFModule\View\Model\PdfModel;
use DateTime;
use Zend\Stdlib\Parameters;

/**
 * Controller that gives some additional details for activities, such as a list of email adresses
 * or an export function specially tailored for the organizer.
 */
class AdminController extends AbstractActionController
{

    /**
     * Return the data of the activity participants
     *
     * @return array
     */
    public function participantsAction()
    {
        $activityId = (int) $this->params('id');
        $signupListId = (int) $this->params('signupList');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $acl = $this->getAcl();
        $identity = $this->getIdentity();

        if ($signupListId === 0) {
            $activity = $queryService->getActivity($activityId);

            if (is_null($activity)) {
                return $this->notFoundAction();
            }

            // If the activity does not have any signup lists there is no need
            // to check the participants or any signup lists.
            if ($activity->getSignupLists()->count() === 0) {
                return $this->notFoundAction();
            }

            if (!$acl->isAllowed($identity, $activity, 'viewParticipants')) {
                $translator = $this->getServiceLocator()->get('translator');
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to view the participants of this activity')
                );
            }
        } else {
            $signupListQueryService = $this->getServiceLocator()->get('activity_service_signupListQuery');
            $signupList = $signupListQueryService->getSignupListByActivity($signupListId, $activityId);

            if (is_null($signupList)) {
                return $this->notFoundAction();
            }

            if (!$acl->isAllowed($identity, $signupList, 'viewParticipants')) {
                $translator = $this->getServiceLocator()->get('translator');
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to view the participants of this activity')
                );
            }

            $activity = $queryService->getActivity($activityId);
        }

        $result = [
            'activity' => $activity,
        ];

        if (isset($signupList)) {
            $result['signupList'] = $signupList;
            $activityAdminSession = new SessionContainer('activityAdminRequest');
            $signupService = $this->getServiceLocator()->get('activity_service_signup');
            $externalSignupForm = $signupService->getExternalAdminForm($signupList);

            if (isset($activityAdminSession->signupData)) {
                $externalSignupForm->setData(new Parameters($activityAdminSession->signupData));
                $externalSignupForm->isValid();
                unset($activityAdminSession->signupData);
            }

            $result['externalSignupForm'] = $externalSignupForm;
            $result['externalSignoffForm'] = new RequestForm(
                'activityExternalSignoff',
                $this->getServiceLocator()->get('translator')->translate('Remove')
            );
        }

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
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

        return $result;
    }

    public function updateAction()
    {
        $activityId = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $activity = $queryService->getActivityWithDetails($activityId);
        $translator = $this->getServiceLocator()->get('translator');

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        $acl = $this->getAcl();
        $identity = $this->getIdentity();

        if (!$acl->isAllowed($identity, $activity, 'update')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to update this activity')
            );
        }

        if ($activity->getSignupLists()->getCount() !== 0) {
            $openingDates = [];

            foreach ($activity->getSignupLists() as $signupList) {
                $startingTimes[$signupList->getId()] = $signupList->getOpenDate();
            }

            if (min($openingDates) < new DateTime()) {
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to update this activity')
                );
            }
        }

        // Can also be `elseif` as SignupLists are guaranteed to be before the
        // Activity begin date and time.
        if ($activity->getBeginTime() < new DateTime()) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to update this activity')
            );
        }

        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $form = $activityService->getActivityForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($activityService->createUpdateProposal($activity, $request->getPost())) {
                $message = $translator->translate('You have successfully created an update proposal for the activity! If the activity was already approved, the proposal will be applied after it has been approved by the board. Otherwise, the update has already been applied to the activity.');

                $this->redirectActivityAdmin(true, $message);
            }
        }

        $updateProposal = $activity->getUpdateProposal();

        if ($updateProposal->count() !== 0) {
            // If there already is an update proposal for this activity, show that instead of the original activity.
            $activity = $updateProposal->first()->getNew();
        }

        $activityData = $activity->toArray();
        $languages = $queryService->getAvailableLanguages($activity);
        $activityData['language_dutch'] = $languages['nl'];
        $activityData['language_english'] = $languages['en'];
        unset($activityData['id'], $activityData['signupLists']);

        $form->setData($activityData);

        $viewModel = new ViewModel(['form' => $form, 'action' => $translator->translate('Update Activity'), 'update' => true]);
        $viewModel->setTemplate('activity/activity/create.phtml');

        return $viewModel;
    }

    public function exportPdfAction()
    {
        $variables = $this->participantsAction();

        $acl = $this->getAcl();
        $identity = $this->getIdentity();

        $resource = isset($variables['signupList']) ? $variables['signupList'] : $variables['activity'];
        if (!$acl->isAllowed($identity, $resource, 'exportParticipants')) {
            $translator = $this->getServiceLocator()->get('translator');
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to export the participants of this activity')
            );
        }

        $pdf = new PdfModel();
        $pdf->setVariables($variables);

        return $pdf;
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
        $acl = $this->getAcl();
        $identity = $this->getIdentity();

        if (!$acl->isAllowed($identity, $signupList, 'adminSignup')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to use this form')
            );
        }

        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $signupService = $this->getServiceLocator()->get('activity_service_signup');

            $form = $signupService->getExternalAdminForm($signupList);
            $postData = $request->getPost();
            $form->setData($postData);

            // Check if the form is valid
            if (!$form->isValid()) {
                $error = $translator->translate('Invalid form');
                $activityAdminSession = new SessionContainer('activityAdminRequest');
                $activityAdminSession->signupData = $postData->toArray();
                $this->redirectActivityAdminRequest($activityId, $signupListId, false, $error, $activityAdminSession);
                return $this->getResponse();
            }

            $formData = $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY);
            $fullName = $formData['fullName'];
            unset($formData['fullName']);
            $email = $formData['email'];
            unset($formData['email']);
            $signupService->adminSignUp($signupList, $fullName, $email, $formData);
            $message = $translator->translate('Successfully subscribed external participant');
            $this->redirectActivityAdminRequest($activityId, $signupListId, true, $message);
            return $this->getResponse();
        }

        $error = $translator->translate('Use the form to subscribe');
        $this->redirectActivityAdminRequest($activityId, $signupListId, false, $error);
        return $this->getResponse();
    }

    public function externalSignoffAction()
    {
        $signupId = (int) $this->params('id');
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $signupMapper = $this->getServiceLocator()->get('activity_mapper_signup');

        $signup = $signupMapper->getSignupById($signupId);

        if (is_null($signup)) {
            return $this->notFoundAction();
        }

        $signupList = $signup->getSignupList();
        $translator = $this->getServiceLocator()->get('translator');
        $acl = $this->getAcl();
        $identity = $this->getIdentity();

        if (!$acl->isAllowed($identity, $signupList, 'adminSignup')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to use this form')
            );
        }

        $request = $this->getRequest();

        //Assure a form is used
        if ($request->isPost()) {
            $form = new RequestForm('activityExternalSignoff', $translator->translate('Remove'));
            $form->setData($request->getPost());

            //Assure the form is valid
            if (!$form->isValid()) {
                $message = $translator->translate('Invalid form');
                $this->redirectActivityAdminRequest(
                    $signupList->getActivity()->getId(),
                    $signupList->getId(),
                    false,
                    $message
                );
                return $this->getResponse();
            }

            $signupService->externalSignOff($signup);
            $message = $translator->translate('Successfully removed external participant');
            $this->redirectActivityAdminRequest(
                $signupList->getActivity()->getId(),
                $signupList->getId(),
                true,
                $message
            );
            return $this->getResponse();
        }

        $message = $translator->translate('Use the form to unsubscribe an external participant');
        $this->redirectActivityAdminRequest(
            $signupList->getActivity()->getId(),
            $signupList->getId(),
            false,
            $message
        );
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
    protected function redirectActivityAdminRequest($activityId, $signupListId, $success, $message, $session = null)
    {
        if (is_null($session)) {
            $session = new SessionContainer('activityAdminRequest');
        }
        $session->success = $success;
        $session->message = $message;
        $this->redirect()->toRoute('activity_admin/participants', [
            'id' => $activityId,
            'signupList' => $signupListId,
        ]);
    }

    /**
     * Show a list of all activities this user can manage.
     */
    public function viewAction()
    {
        $admin = false;
        $acl = $this->getAcl();
        $identity = $this->getIdentity();
        
        if (!$acl->isAllowed($identity, 'activity', 'viewAdmin')) {
            $translator = $this->getServiceLocator()->get('translator');
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to administer activities')
            );
        }
        
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $disapprovedActivities = null;
        $unapprovedActivities = null;
        $approvedActivities = null;

        if ($acl->isAllowed($identity, 'activity', 'approval')) {
            $admin = true;
            $disapprovedActivities = $queryService->getDisapprovedActivities();
            $unapprovedActivities = $queryService->getUnapprovedActivities();
            $approvedActivities = $queryService->getApprovedActivities();
        }

        $paginator = new Paginator($queryService->getOldCreatedActivitiesPaginator($identity));
        $paginator->setDefaultItemCountPerPage(15);
        $page = $this->params()->fromRoute('page');
        if ($page && $paginator->count() !== 0) {
            $paginator->setCurrentPageNumber($paginator->normalizePageNumber($page));
        }

        $result = [
            'upcomingActivities' => $queryService->getUpcomingCreatedActivities($identity),
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

        return $result;
    }

    protected function getAcl()
    {
        return $this->getServiceLocator()->get('activity_acl');
    }

    protected function getIdentity()
    {
        return $this->getServiceLocator()->get('user_service_user')->getIdentity();
    }

    protected function redirectActivityAdmin($success, $message)
    {
        $activityAdminSession = new SessionContainer('activityAdmin');
        $activityAdminSession->success = $success;
        $activityAdminSession->message = $message;
        $this->redirect()->toRoute('activity_admin');
    }
}
