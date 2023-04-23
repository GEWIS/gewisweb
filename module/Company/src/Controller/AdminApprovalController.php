<?php

declare(strict_types=1);

namespace Company\Controller;

use Application\Form\ModifyRequest as RequestForm;
use Company\Mapper\{
    Company as CompanyMapper,
    Job as JobMapper,
};
use Company\Service\{
    AclService,
    Company as CompanyService,
};
use Application\Model\Enums\ApprovableStatus;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * @method FlashMessenger flashMessenger()
 */
class AdminApprovalController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly CompanyMapper $companyMapper,
        private readonly JobMapper $jobMapper,
        private readonly CompanyService $companyService,
    ) {
    }

    public function indexAction(): ViewModel
    {
        $approveCompanies = $this->aclService->isAllowed('approve', 'company');
        $approveJobs = $this->aclService->isAllowed('approve', 'job');

        if (
            !$approveCompanies
            && !$approveJobs
        ) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the approval status of jobs')
            );
        }

        $companies = [];
        if ($approveCompanies) {
            $companies = $this->companyMapper->findUpdateProposals();
        }

        $jobs = [];
        if ($approveJobs) {
            $jobs = $this->jobMapper->findProposals();
        }

        return new ViewModel(
            [
                'companies' => $companies,
                'jobs' => $jobs,
            ]
        );
    }

    public function jobApprovalAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('approve', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the approval status of jobs')
            );
        }

        // Get parameter and find specific job.
        $jobId = (int) $this->params()->fromRoute('jobId');
        $job = $this->companyService->getJobById($jobId);

        // Check the job is found. If not, throw 404.
        if (null === $job) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'job' => $job,
                'approvalForm' => new RequestForm(
                    'updateJobApprovalStatus',
                    $this->translator->translate('Approve Job'),
                ),
                'disapprovalForm' => new RequestForm(
                    'updateJobApprovalStatus',
                    $this->translator->translate('Disapprove Job'),
                ),
                'resetForm' => new RequestForm(
                    'updateJobApprovalStatus',
                    $this->translator->translate('Reset Approval Status'),
                ),
            ]
        );
    }

    public function changeJobApprovalStatusAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('approve', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the approval status of jobs')
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        // Get parameter and find specific job.
        $jobId = (int) $this->params()->fromRoute('jobId');
        $job = $this->companyService->getJobById($jobId);

        // Check the job is found. If not, throw 404.
        if (null === $job) {
            return $this->notFoundAction();
        }

        $form = new RequestForm('updateJobApprovalStatus');
        $form->setData($request->getPost()->toArray());

        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        $changeType = $this->params()->fromRoute('type');

        switch ($changeType) {
            case 'approve':
                $this->companyService->setJobApproval(
                    $job,
                    ApprovableStatus::Approved,
                );
                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Job approved!'));
                break;
            case 'disapprove':
                $this->companyService->setJobApproval(
                    $job,
                    ApprovableStatus::Rejected,
                    $request->getPost()->get('message'),
                );
                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Job disapproved!'));
                break;
            case 'reset':
                $this->companyService->resetJobApproval($job);
                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Job approval status reset!'));
                break;
        };

        return $this->redirect()->toRoute('company_admin_approval');
    }

    public function jobProposalAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('approve', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to approve update proposals of jobs')
            );
        }

        $proposalId = (int) $this->params()->fromRoute('proposalId');
        $proposal = $this->jobMapper->findProposal($proposalId);

        if (null === $proposal) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'proposal' => $proposal,
                'proposalApplyForm' => new RequestForm(
                    'updateJobProposalStatus',
                    $this->translator->translate('Apply Update'),
                ),
                'proposalRejectForm' => new RequestForm(
                    'updateJobProposalStatus',
                    $this->translator->translate('Reject Update'),
                ),
            ]
        );
    }

    public function changeJobProposalStatusAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('approve', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to approve update proposals of jobs')
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        $proposalId = (int) $this->params()->fromRoute('proposalId');
        $proposal = $this->jobMapper->findProposal($proposalId);

        if (null === $proposal) {
            return $this->notFoundAction();
        }

        $form = new RequestForm('updateJobProposalStatus');
        $form->setData($request->getPost()->toArray());

        if (!$form->isValid()) {
            $this->flashMessenger()->addErrorMessage($this->translator->translate('An unknown error occurred while try to change the status of a job update proposal. Please try again.'));

            return $this->redirect()->toRoute('company_admin_approval/job_proposal', ['proposalId' => $proposalId]);
        }

        $changeType = $this->params()->fromRoute('type');

        switch ($changeType) {
            case 'apply':
                $this->companyService->applyJobProposal($proposal);
                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Job has been updated!'));
                break;
            case 'cancel':
                $this->companyService->cancelJobProposal(
                    $proposal,
                    $request->getPost()->get('message'),
                );
                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Job update has been rejected!'));
                break;
        }

        return $this->redirect()->toRoute('company_admin_approval');
    }
}
