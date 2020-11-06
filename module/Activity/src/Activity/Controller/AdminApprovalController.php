<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Activity\Model\Activity;
use Activity\Form\ModifyRequest as RequestForm;

/**
 * Controller for all administrative activity actions
 */
class AdminApprovalController extends AbstractActionController
{
    /**
     * View one activity.
     */
    public function viewAction()
    {
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $acl = $translator = $this->getServiceLocator()->get('activity_acl');
        $identity = $this->getServiceLocator()->get('user_role');

        if (!$acl->isAllowed($identity, 'activity', 'approval')) {
            $translator = $this->getServiceLocator()->get('translator');
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the approval of this activity')
            );
        }

        /** @var $activity Activity*/
        $activity = $queryService->getActivity($id);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        return [
            'activity' => $activity,
            'approvalForm' => new RequestForm('updateApprovalStatus', 'Approve'),
            'disapprovalForm' => new RequestForm('updateApprovalStatus', 'Disapprove'),
            'resetForm' => new RequestForm('updateApprovalStatus', 'Reset')
        ];
    }

    /**
     * Approve of an activity
     */
    public function approveAction()
    {
        return $this->setApprovalStatus('approve');
    }

    /**
     * Disapprove an activity
     */
    public function disapproveAction()
    {
        return $this->setApprovalStatus('disapprove');
    }

    /**
     * Reset the approval status of an activity
     */
    public function resetAction()
    {
        return $this->setApprovalStatus('reset');
    }

    /**
     * Display the proposed update
     */
    public function viewProposalAction()
    {
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        $proposal = $queryService->getProposal($id);

        if (is_null($proposal)) {
            return $this->notFoundAction();
        }

        return [
            'proposal' => $proposal,
            'proposalApplyForm' => new RequestForm('proposalApply', 'Apply update'),
            'proposalRevokeForm' => new RequestForm('proposalRevoke', 'Revoke update')
        ];
    }

    /**
     * Apply the proposed update
     */
    public function applyProposalAction()
    {
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');

        //Assure the form is used
        if (!$this->getRequest()->isPost()) {
            return $this->notFoundAction();
        }
        $form = new RequestForm('proposalApply');

        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        $proposal = $queryService->getProposal($id);
        if (is_null($proposal)) {
            return $this->notFoundAction();
        }
        $oldId = $proposal->getOld()->getId();
        $activityService->updateActivity($proposal);

        $this->redirect()->toRoute('activity_admin_approval/view', [
            'id' => $oldId,
        ]);
    }

    /**
     * Revoke the proposed update
     */
    public function revokeProposalAction()
    {
        $id = (int) $this->params('id');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        //Assure the form is used
        if (!$this->getRequest()->isPost()) {
            return $this->notFoundAction();
        }
        $form = new RequestForm('proposalRevoke');

        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        $proposal = $queryService->getProposal($id);
        if (is_null($proposal)) {
            return $this->notFoundAction();
        }

        $oldId = $proposal->getOld()->getId();
        $activityService->revokeUpdateProposal($proposal);

        $this->redirect()->toRoute('activity_admin_approval/view', [
            'id' => $oldId,
        ]);
    }

    /**
     * Set the approval status of the activity requested
     *
     * @param $status
     * @return array|\Zend\Http\Response
     */
    protected function setApprovalStatus($status)
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $queryService = $this->getServiceLocator()->get('activity_service_activityQuery');

        /** @var $activity Activity*/
        $activity = $queryService->getActivity($id);

        //Assure the form is used
        if (!$this->getRequest()->isPost()) {
            return $this->notFoundAction();
        }
        $form = new RequestForm('updateApprovalStatus');

        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        switch ($status) {
            case 'approve':
                $activityService->approve($activity);
                break;
            case 'disapprove':
                $activityService->disapprove($activity);
                break;
            case 'reset':
                $activityService->reset($activity);
                break;
            default:
                throw new \InvalidArgumentException('No such status ' . $status);

        }

        return $this->redirect()->toRoute('activity_admin');
    }
}
