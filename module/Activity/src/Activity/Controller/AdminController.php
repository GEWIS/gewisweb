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
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $translatorService = $this->getServiceLocator()->get('activity_service_activityTranslator');
        $langSession = new SessionContainer('lang');
        $signupRequestSession = new SessionContainer('signupRequest');

        /** @var $activity Activity*/
        $activity = $queryService->getActivityWithDetails($id);
        $translatedActivity = $translatorService->getTranslatedActivity($activity, $langSession->lang);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        $signupService = $this->getServiceLocator()->get('activity_service_signup');
        $externalSignupForm = $signupService->getForm($activity->getFields(), true);

        $result = [
            'activity' => $translatedActivity,
            'signupData' => $translatorService->getTranslatedSignedUpData($activity, $langSession->lang),
            'externalSignupForm' => $externalSignupForm,
        ];
        //Retrieve and clear the request status from the session, if it exists.
        if (isset($signupRequestSession->success)){
            $result['success'] = $signupRequestSession->success;
            unset($signupRequestSession->success);
            $result['message'] = $signupRequestSession->message;
            unset($signupRequestSession->message);
        }
        return $result;
    }

    public function updateAction()
    {
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $activity = $queryService->getActivityWithDetails($id);

        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $form = $activityService->getForm();

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $activityService->createUpdateProposal(
                    $activity,
                    $form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY),
                    $postData['language_dutch'],
                    $postData['language_english']
                );
                $view = new ViewModel();
                $view->setTemplate('activity/activity/updateSuccess.phtml');
                return $view;
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
        if (!$this->getRequest()->isPost()){
            $error = $translator->translate('Use the form to subscribe');
            $this->redirectSignupRequest($id, false, $error);
            return;
        }

        $form = $signupService->getForm($activity->getFields(), true);
        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()){
            $error = $translator->translate('Invalid form');
            $this->redirectSignupRequest($id, false, $error);
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

    /**
     * Redirects to the view of the activity with the given $id, where the
     * $error message can be displayed if the request was unsuccesful (i.e.
     * $success was false)
     *
     * @param int $id
     * @param boolean $success Whether the request was successful
     * @param string $message
     */
    protected function redirectSignupRequest($id, $success, $message)
    {
        $signupRequestSession = new SessionContainer('signupRequest');
        $signupRequestSession->success = $success;
        $signupRequestSession->message = $message;
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

        return [
            'upcomingActivities' => $queryService->getUpcomingCreatedActivities($user),
            'disapprovedActivities' => $disapprovedActivities,
            'unapprovedActivities' => $unapprovedActivities,
            'approvedActivities' => $approvedActivities,
            'oldActivityPaginator' => $paginator,
            'admin' => $admin,
                ];
    }
}
