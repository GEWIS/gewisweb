<?php

namespace Company\Controller;

use Application\Form\ModifyRequest as RequestForm;
use Company\Service\{
    AclService,
    Company as CompanyService,
};
use Application\Model\Enums\ApprovableStatus;
use Laminas\Http\Response;
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
        private readonly CompanyService $companyService,
    ) {
    }

    public function jobApprovalAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('approve', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the approval status of jobs')
            );
        }

        // Get parameters.
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');

        // Find the specified job.
        $job = $this->companyService->getJobById($jobId);

        // Check the job is found. If not, throw 404.
        if (
            null === $job
            || $job->getPackage()->getId() !== $packageId
            || $job->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'job' => $job,
                'formUrlData' => [
                    'companySlugName' => $companySlugName,
                    'packageId' => $packageId,
                    'jobId' => $jobId,
                ],
                'approvalForm' => new RequestForm(
                    'updateJobApprovalStatus',
                    $this->translator->translate('Approve'),
                ),
                'disapprovalForm' => new RequestForm(
                    'updateJobApprovalStatus',
                    $this->translator->translate('Disapprove'),
                ),
                'resetForm' => new RequestForm(
                    'updateJobApprovalStatus',
                    $this->translator->translate('Reset'),
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

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        // Get parameters.
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');

        // Find the specified job.
        $job = $this->companyService->getJobById($jobId);

        // Check the job is found. If not, throw 404.
        if (
            null === $job
            || $job->getPackage()->getId() !== $packageId
            || $job->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        $form = new RequestForm('updateJobApprovalStatus');
        $form->setData($request->getPost()->toArray());

        if (!$form->isValid()) {
            return $this->notFoundAction();
        }

        $changeType = $this->params('type');

        switch ($changeType) {
            case 'approve':
                $this->companyService->setJobApproval(
                    $job,
                    ApprovableStatus::Approved,
                    $request->getPost()->get('message'),
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

        return $this->redirect()->toRoute(
            'company_admin/company/edit/package/edit',
            [
                'companySlugName' => $companySlugName,
                'packageId' => $packageId,
            ],
        );
    }
}
