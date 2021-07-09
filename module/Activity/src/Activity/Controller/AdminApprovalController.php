<?php

namespace Activity\Controller;

use Activity\Form\ModifyRequest as RequestForm;
use Activity\Model\Activity;
use Activity\Service\ActivityQuery;
use InvalidArgumentException;
use User\Permissions\NotAllowedException;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller for all administrative activity actions
 */
class AdminApprovalController extends AbstractActionController
{

    /**
     * @var \Activity\Service\Activity
     */
    private $activityService;

    /**
     * @var ActivityQuery
     */
    private $activityQueryService;

    public function __construct(\Activity\Service\Activity $activityService, ActivityQuery $activityQueryService)
    {
        $this->activityService = $activityService;
        $this->activityQueryService = $activityQueryService;
    }

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $id = (int) $this->params('id');

        $acl = $this->getServiceLocator()->get('activity_acl');
        $identity = $this->getServiceLocator()->get('user_role');

        if (!$acl->isAllowed($identity, 'activity', 'approval')) {
            $translator = $this->getServiceLocator()->get('translator');
            throw new NotAllowedException(
                $translator->translate('You are not allowed to view the approval of this activity')
            );
        }

        /** @var $activity Activity */
        $activity = $this->activityQueryService->getActivity($id);

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
     * Set the approval status of the activity requested
     *
     * @param $status
     * @return array|Response
     */
    protected function setApprovalStatus($status)
    {
        $id = (int) $this->params('id');

        /** @var $activity Activity */
        $activity = $this->activityQueryService->getActivity($id);

        //Assure the form is used
        if (!$this->getRequest()->isPost()) {
            return $this->notFoundAction();
        }
        $form = new RequestForm('updateApprovalStatus');

        $form->setData($this->getRequest()->getPost());

        //Assure the form is valid
        if (!$form->isValid() || is_null($activity)) {
            return $this->notFoundAction();
        }

        switch ($status) {
            case 'approve':
                $this->activityService->approve($activity);
                break;
            case 'disapprove':
                $this->activityService->disapprove($activity);
                break;
            case 'reset':
                $this->activityService->reset($activity);
                break;
            default:
                throw new InvalidArgumentException('No such status ' . $status);

        }

        return $this->redirect()->toRoute('activity_admin');
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

        $proposal = $this->activityQueryService->getProposal($id);

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

        $proposal = $this->activityQueryService->getProposal($id);
        if (is_null($proposal)) {
            return $this->notFoundAction();
        }
        $newId = $proposal->getNew()->getId();
        $this->activityService->updateActivity($proposal);

        $this->redirect()->toRoute('activity_admin_approval/view', [
            'id' => $newId,
        ]);
    }

    /**
     * Revoke the proposed update
     */
    public function revokeProposalAction()
    {
        $id = (int) $this->params('id');
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

        $proposal = $this->activityQueryService->getProposal($id);
        if (is_null($proposal)) {
            return $this->notFoundAction();
        }

        $oldId = $proposal->getOld()->getId();
        $this->activityService->revokeUpdateProposal($proposal);

        $this->redirect()->toRoute('activity_admin_approval/view', [
            'id' => $oldId,
        ]);
    }
}
