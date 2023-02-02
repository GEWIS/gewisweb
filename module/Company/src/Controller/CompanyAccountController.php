<?php

namespace Company\Controller;

use Application\Model\Enums\ApprovableStatus;
use Company\Form\JobsTransfer as JobsTransferForm;
use Company\Mapper\{
    Package as JobPackageMapper,
    Job as JobMapper,
};
use Company\Model\CompanyJobPackage as CompanyJobPackageModel;
use Company\Service\{
    AclService,
    Company as CompanyService,
};
use DateTime;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * @method FlashMessenger flashMessenger()
 */
class CompanyAccountController extends AbstractActionController
{
    /**
     * CompanyAccountController constructor.
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly JobMapper $jobMapper,
        private readonly JobPackageMapper $jobPackageMapper,
        private readonly JobsTransferForm $jobsTransferForm,
        private readonly CompanyService $companyService,
    ) {
    }

    public function selfAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the company accounts')
            );
        }

        return new ViewModel([]);
    }

    public function settingsAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the company accounts')
            );
        }

        return new ViewModel([]);
    }

    public function jobsAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the company accounts')
            );
        }

        $result = [];
        $company = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany();

        // `packageId` is an optional part of the route and can be used to display jobs specific to that job package. It
        // is null if it was not specified (`jobs_overview` route).
        if (null !== ($packageId = $this->params('packageId'))) {
            /** @var CompanyJobPackageModel|null $package */
            $package = $this->jobPackageMapper->find($packageId);

            // Check if the package exists and if it belongs to the company of the company user.
            if (
                null === $package
                || $package->getCompany()->getSlugName() !== $company->getSlugName()
            ) {
                return $this->notFoundAction();
            }

            $result['jobs'] = $package->getJobsWithoutProposals();
            $result['package'] = $package;
        } else {
            $result['packages'] = $this->jobPackageMapper->findJobPackagesByCompany($company);
            $result += [
                'unapproved' => $this->jobMapper->findRecentByApprovedStatus(
                    ApprovableStatus::Unapproved,
                    $company->getSlugName(),
                ),
                'approved' => $this->jobMapper->findRecentByApprovedStatus(
                    ApprovableStatus::Approved,
                    $company->getSlugName(),
                ),
                'rejected' => $this->jobMapper->findRecentByApprovedStatus(
                    ApprovableStatus::Rejected,
                    $company->getSlugName(),
                ),
            ];
        }

        return new ViewModel($result);
    }

    public function addJobAction(): ViewModel|Response
    {
        if (!$this->aclService->isAllowed('createOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create jobs')
            );
        }

        // Get the specified package and company user (through ACL, as it is already included).
        $packageId = $this->params()->fromRoute('packageId');
        /** @var CompanyJobPackageModel|null $package */
        $package = $this->jobPackageMapper->find($packageId);
        $companySlugName = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany()->getSlugName();

        // Check if the package exists and if it belongs to the company of the company user.
        if (
            null === $package
            || $package->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        if ((new DateTime()) >= $package->getExpirationDate()) {
            $this->flashMessenger()->addErrorMessage(
                $this->translator->translate('You cannot create new jobs in expired job packages.')
            );

            return $this->redirect()->toRoute('company_account/jobs', ['packageId' => $packageId]);
        }

        $jobForm = $this->companyService->getJobForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $jobForm->setData($post);
            $jobForm->setCompanySlug($companySlugName);

            if ($jobForm->isValid()) {
                if (false !== $this->companyService->createJob($package, $jobForm->getData())) {
                    $this->flashMessenger()->addSuccessMessage(
                        $this->translator->translate('Job proposal successfully created! It will become active after it has been approved.')
                    );

                    return $this->redirect()->toRoute(
                        'company_account/jobs',
                        [
                            'packageId' => $packageId,
                        ]
                    );
                }
            }
        }

        // Initialize the form
        $jobForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'company_account/jobs/add',
                [
                    'packageId' => $packageId,
                ]
            )
        );

        // Initialize the view
        return new ViewModel(
            [
                'form' => $jobForm,
            ]
        );
    }

    public function editJobAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('editOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit jobs')
            );
        }

        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');
        $companySlugName = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany()->getSlugName();
        $job = $this->jobMapper->findByPackageAndCompany(
            $companySlugName,
            $packageId,
            $jobId,
        );

        // Check if the job exists (and the associated package) and that it belongs to the company of the company user.
        if (null === $job) {
            return $this->notFoundAction();
        }

        if ((new DateTime()) >= $job->getPackage()->getExpirationDate()) {
            $this->flashMessenger()->addErrorMessage(
                $this->translator->translate('You cannot update jobs in expired job packages.')
            );

            return $this->redirect()->toRoute('company_account/jobs', ['packageId' => $packageId]);
        }

        $jobForm = $this->companyService->getJobForm();
        $updateProposals = $job->getUpdateProposals();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $jobForm->setData($post);
            $jobForm->setCompanySlug($companySlugName);
            $jobForm->setCurrentSlug($job->getSlugName());

            if ($jobForm->isValid()) {
                if (false !== $this->companyService->updateJob($job, $jobForm->getData())) {
                    $this->flashMessenger()->addSuccessMessage(
                        $this->translator->translate('Update proposal for job successfully created! It will become active after it has been approved.')
                    );

                    return $this->redirect()->toRoute(
                        'company_account/jobs',
                        [
                            'packageId' => $packageId,
                        ]
                    );
                }
            }
        }

        // Do not do this before the form validation, because otherwise you get an update to an update, while we always
        // want updates of a real job.
        if (0 !== $updateProposals->count()) {
            // If there are already updates proposed for this job, show the last update proposal instead.
            $job = $updateProposals->last()->getProposal();
        }

        // Initialize the form
        $jobData = $job->toArray();
        $jobData['language_dutch'] = null !== $jobData['description'];
        $jobData['language_english'] = null !== $jobData['descriptionEn'];
        $jobData['category'] = $job->getCategory()->getId();
        $jobForm->setData($jobData);
        $jobForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'company_account/jobs/edit',
                [
                    'packageId' => $packageId,
                    'jobId' => $jobId,
                ]
            )
        );

        $isJobRejected = false;
        $jobRejectedMessage = null;
        if (ApprovableStatus::Rejected === $job->getApproved()) {
            $isJobRejected = true;
            $jobRejectedMessage = $job->getApprovableText()?->getMessage();
        }

        // Initialize the view
        return new ViewModel(
            [
                'form' => $jobForm,
                'attachments' => $job->getAttachment(),
                'isJobRejected' => $isJobRejected,
                'isJobUpdate' => $job->getIsUpdate(),
                'jobRejectedMessage' => $jobRejectedMessage,
            ]
        );
    }

    public function deleteJobAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('deleteOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete jobs')
            );
        }

        if (!$this->getRequest()->isPost()) {
            return $this->notFoundAction();
        }

        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');
        $companySlugName = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany()->getSlugName();
        $job = $this->jobMapper->findByPackageAndCompany(
            $companySlugName,
            $packageId,
            $jobId,
        );

        // Check if the job exists (and the associated package) and that it belongs to the company of the company user.
        if (null === $job) {
            return $this->notFoundAction();
        }

        $this->companyService->deleteJob($job);
        $this->flashMessenger()->addSuccessMessage($this->translator->translate('Job successfully deleted.'));

        return $this->redirect()->toRoute('company_account/jobs', ['packageId' => $packageId]);
    }

    public function statusJobAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('statusOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the status of a job')
            );
        }

        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');
        $companySlugName = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany()->getSlugName();
        $job = $this->jobMapper->findByPackageAndCompany(
            $companySlugName,
            $packageId,
            $jobId,
        );

        // Check if the job exists (and the associated package) and that it belongs to the company of the company user.
        if (null === $job) {
            return $this->notFoundAction();
        }

        return new ViewModel([]);
    }

    public function transferJobsAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('transferOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to transfer jobs')
            );
        }

        // Get the specified package and company user (through ACL, as it is already included).
        $packageId = $this->params()->fromRoute('packageId');
        /** @var CompanyJobPackageModel|null $package */
        $package = $this->jobPackageMapper->find($packageId);
        $company = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany();
        $companySlugName = $company->getSlugName();

        // Check if the package exists and if it belongs to the company of the company user.
        if (
            null === $package
            || $package->getCompany()->getSlugName() !== $companySlugName
            || !$package->isExpired()
        ) {
            return $this->notFoundAction();
        }

        $form = $this->jobsTransferForm;
        $form->populateValueOptions(
            $package->getJobsWithoutProposals()->toArray(),
            $this->jobPackageMapper->findNonExpiredPackages($company),
        );

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->companyService->transferJobs($form->getData())) {
                    $this->flashMessenger()->addSuccessMessage(
                        $this->translator->translate('Jobs successfully transferred')
                    );

                    return $this->redirect()->toRoute(
                        'company_account/jobs',
                        ['packageId' => (int) $form->getData()['packages']],
                    );
                } else {
                    $this->flashMessenger()->addErrorMessage(
                        $this->translator->translate(
                            'An unknown error occurred while trying to transfer jobs, please try again.'
                        )
                    );
                }
            }
        }

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function highlightsAction(): ViewModel
    {
        return new ViewModel([]);
    }

    public function bannerAction(): ViewModel
    {
        return new ViewModel([]);
    }
}
