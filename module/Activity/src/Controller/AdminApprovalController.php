<?php

namespace Activity\Controller;

use Activity\Service\{
    AclService,
    Activity as ActivityService,
    ActivityQuery as ActivityQueryService,
};
use Application\Form\ModifyRequest as RequestForm;
use InvalidArgumentException;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * Controller for all administrative activity actions.
 */
class AdminApprovalController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityService $activityService,
        private readonly ActivityQueryService $activityQueryService,
    ) {
    }

    /**
     * View one activity.
     */
    public function viewAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');

        if (!$this->aclService->isAllowed('approval', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the approval of this activity')
            );
        }

        $activity = $this->activityQueryService->getActivity($id);

        if (null === $activity) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'activity' => $activity,
                'approvalForm' => new RequestForm('updateApprovalStatus', 'Approve'),
                'disapprovalForm' => new RequestForm('updateApprovalStatus', 'Disapprove'),
                'resetForm' => new RequestForm('updateApprovalStatus', 'Reset'),
            ]
        );
    }

    /**
     * Approve of an activity.
     */
    public function approveAction(): Response|ViewModel
    {
        return $this->setApprovalStatus('approve');
    }

    /**
     * Set the approval status of the activity requested.
     *
     * @param string $status
     *
     * @return ViewModel|Response
     */
    protected function setApprovalStatus(string $status): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        // Assure the form is used
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        $id = (int) $this->params()->fromRoute('id');
        $activity = $this->activityQueryService->getActivity($id);

        if (null === $activity) {
            return $this->notFoundAction();
        }

        $form = new RequestForm('updateApprovalStatus');
        $form->setData($request->getPost()->toArray());

        //Assure the form is valid
        if (!$form->isValid()) {
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
     * Disapprove an activity.
     */
    public function disapproveAction(): Response|ViewModel
    {
        return $this->setApprovalStatus('disapprove');
    }

    /**
     * Reset the approval status of an activity.
     */
    public function resetAction(): Response|ViewModel
    {
        return $this->setApprovalStatus('reset');
    }

    /**
     * Display the proposed update.
     */
    public function viewProposalAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');

        $proposal = $this->activityQueryService->getProposal($id);

        if (null === $proposal) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'proposal' => $proposal,
                'proposalApplyForm' => new RequestForm('proposalApply', 'Apply update'),
                'proposalRevokeForm' => new RequestForm('proposalRevoke', 'Revoke update'),
            ]
        );
    }

    /**
     * Apply the proposed update.
     */
    public function applyProposalAction(): Response|ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');
        /** @var Request $request */
        $request = $this->getRequest();

        //Assure the form is used
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }
        $form = new RequestForm('proposalApply');

        $form->setData($request->getPost()->toArray());

        //Assure the form is valid
        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        $proposal = $this->activityQueryService->getProposal($id);
        if (null === $proposal) {
            return $this->notFoundAction();
        }
        $newId = $proposal->getNew()->getId();
        $this->activityService->updateActivity($proposal);

        return $this->redirect()->toRoute(
            'activity_admin_approval/view',
            [
                'id' => $newId,
            ]
        );
    }

    /**
     * Revoke the proposed update.
     */
    public function revokeProposalAction(): Response|ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');
        /** @var Request $request */
        $request = $this->getRequest();

        //Assure the form is used
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }
        $form = new RequestForm('proposalRevoke');

        $form->setData($request->getPost()->toArray());

        //Assure the form is valid
        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        $proposal = $this->activityQueryService->getProposal($id);
        if (null === $proposal) {
            return $this->notFoundAction();
        }

        $oldId = $proposal->getOld()->getId();
        $this->activityService->revokeUpdateProposal($proposal);

        return $this->redirect()->toRoute(
            'activity_admin_approval/view',
            [
                'id' => $oldId,
            ]
        );
    }
}
