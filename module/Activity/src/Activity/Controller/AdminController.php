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
        $id = (int)$this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $translatorService = $this->getServiceLocator()->get('activity_service_activityTranslator');
        $langSession = new SessionContainer('lang');
        $translator = $this->getServiceLocator()->get('activity_service_activity')->getTranslator();
        $signupRequestSession = new SessionContainer('signupRequest');

        /** @var $activity Activity */
        $activity = $queryService->getActivityWithDetails($id);
        $translatedActivity = $translatorService->getTranslatedActivity($activity, $langSession->lang);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $externalSignupForm = $signupService->getExternalAdminForm($activity->getFields());
        if (isset($signupRequestSession->signupData)) {
            $externalSignupForm->setData(new Parameters($signupRequestSession->signupData));
            $externalSignupForm->isValid();
            unset($signupRequestSession->signupData);
        }

        $result = [
            'activity' => $translatedActivity,
            'signupData' => $translatorService->getTranslatedSignedUpData($activity, $langSession->lang),
            'externalSignupForm' => $externalSignupForm,
            'externalSignoffForm' => new RequestForm('activityExternalSignoff', $translator->translate('Remove')),
        ];
        //Retrieve and clear the request status from the session, if it exists.
        if (isset($signupRequestSession->success)) {
            $result['success'] = $signupRequestSession->success;
            unset($signupRequestSession->success);
            $result['message'] = $signupRequestSession->message;
            unset($signupRequestSession->message);
        }
        return $result;
    }

    public function updateAction()
    {
        $id = (int)$this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $activity = $queryService->getActivityWithDetails($id);

        if ($activity->getEndTime() < new DateTime()) {
            $acl = $this->getServiceLocator()->get('activity_service_activity')->getAcl();
            $user = $this->getServiceLocator()->get('user_service_user')->getIdentity();
            if (!$acl->isAllowed($user, 'activity', 'update')) {
                //Only admins may update old activities
                $translator = $this->getServiceLocator()->get('translator');
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to update old activities')
                );
            }
        }

        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $form = $activityService->getForm();

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $updated = $activityService->createUpdateProposal(
                    $activity,
                    $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY),
                    $postData['language_dutch'],
                    $postData['language_english']
                );
                $translator = $this->getServiceLocator()->get('translator');
                $message = $translator->translate('The activity has been successfully updated.');
                if (!$updated) {
                    $message .= ' ' . $translator->translate('It will become applied after it has been approved by the board.');
                }
                $this->redirectActivityAdmin(true, $message);
            }
        }
        $updateProposal = $activity->getUpdateProposal();
        if ($updateProposal->count() !== 0) {
            //if there exists an update proposal, show that instead of the old activity
            $activity = $updateProposal->first()->getNew();
        }
        $form->bind($activity);
        $languages = $queryService->getAvailableLanguages($activity);

        return ['form' => $form, 'activity' => $activity, 'languages' => $languages];
    }

    public function exportPdfAction()
    {
        $pdf = new PdfModel();
        $pdf->setVariables($this->participantsAction());
        return $pdf;
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
            $this->redirectSignupRequest($id, false, $error);
            return;
        }

        $form = $signupService->getExternalAdminForm($activity->getFields());
        $postData = $this->getRequest()->getPost();
        $form->setData($postData);

        //Assure the form is valid
        if (!$form->isValid()) {
            $error = $translator->translate('Invalid form');
            $signupRequestSession = new SessionContainer('signupRequest');
            $signupRequestSession->signupData = $postData->toArray();
            $this->redirectSignupRequest($id, false, $error, $signupRequestSession);
            return;
        }

        $formData = $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY);
        $fullName = $formData['fullName'];
        unset($formData['fullName']);
        $email = $formData['email'];
        unset($formData['email']);
        $signupService->adminSignUp($activity, $fullName, $email, $formData);
        $message = $translator->translate('Successfully subscribed external participant');
        $this->redirectSignupRequest($id, true, $message);
    }

    public function externalSignoffAction()
    {
        $id = (int) $this->params('id');

        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $signupMapper = $this->getServiceLocator()->get('activity_mapper_signup');

        $signup = $signupMapper->getSignupById($id);

        if (is_null($signup)) {
            return $this->notFoundAction();
        }
        $activity = $signup->getActivity();
        $translator = $activityService->getTranslator();

        //Assure a form is used
        if (!$this->getRequest()->isPost()) {
            $message = $translator->translate('Use the form to unsubscribe an external participant');
            $this->redirectSignupRequest($activity->getId(), false, $message);
            return;
        }

        $form = new RequestForm('activityExternalSignoff', $translator->translate('Remove'));
        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()) {
            $message = $translator->translate('Invalid form');
            $this->redirectSignupRequest($activity->getId(), false, $message);
            return;
        }

        $signupService->externalSignOff($signup);
        $message = $translator->translate('Successfully removed external participant');
        $this->redirectSignupRequest($activity->getId(), true, $message);
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
    protected function redirectSignupRequest($id, $success, $message, $session = null)
    {
        if (is_null($session)) {
            $session = new SessionContainer('signupRequest');
        }
        $session->success = $success;
        $session->message = $message;
        $this->redirect()->toRoute('activity_admin/participants', [
            'id' => $id,
        ]);
    }

    /**
     * Show a list of all activities this user can manage.
     */
    public function viewAction()
    {
        $admin = false;
        $acl = $this->getServiceLocator()->get('activity_service_activity')->getAcl();
        $user = $this->getServiceLocator()->get('user_service_user')->getIdentity();
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $disapprovedActivities = null;
        $unapprovedActivities = null;
        $approvedActivities = null;
        if ($acl->isAllowed($user, 'activity', 'approve')) {
            $admin = true;
            $disapprovedActivities = $queryService->getDisapprovedActivities();
            $unapprovedActivities = $queryService->getUnapprovedActivities();
            $approvedActivities = $queryService->getApprovedActivities();
        }

        $paginator = new Paginator($queryService->getOldCreatedActivitiesPaginator($user));
        $paginator->setDefaultItemCountPerPage(15);
        $page = $this->params()->fromRoute('page');
        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        $result = [
            'upcomingActivities' => $queryService->getUpcomingCreatedActivities($user),
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

    protected function redirectActivityAdmin($success, $message)
    {
        $activityAdminSession = new SessionContainer('activityAdmin');
        $activityAdminSession->success = $success;
        $activityAdminSession->message = $message;
        $this->redirect()->toRoute('activity_admin');
    }
}
