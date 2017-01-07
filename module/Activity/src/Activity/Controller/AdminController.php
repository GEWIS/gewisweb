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


        /** @var $activity Activity*/
        $activity = $queryService->getActivityWithDetails($id);
        $translatedActivity = $translatorService->getTranslatedActivity($activity, $langSession->lang);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        return [
            'activity' => $translatedActivity,
            'signupData' => $translatorService->getTranslatedSignedUpData($activity, $langSession->lang),
        ];
    }

    public function updateAction()
    {
        $id = (int) $this->params('id');
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
        if (isset($activityAdminSession->success)){
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
