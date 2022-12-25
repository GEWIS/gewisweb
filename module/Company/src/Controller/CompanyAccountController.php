<?php

namespace Company\Controller;

use Application\Model\Enums\ApprovableStatus;
use Company\Mapper\Package as JobPackageMapper;
use Company\Service\{
    AclService,
    Company as CompanyService,
};
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class CompanyAccountController extends AbstractActionController
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var JobPackageMapper
     */
    private JobPackageMapper $jobPackageMapper;

    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    /**
     * CompanyAccountController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param JobPackageMapper $jobPackageMapper
     * @param CompanyService $companyService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        JobPackageMapper $jobPackageMapper,
        CompanyService $companyService,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->jobPackageMapper = $jobPackageMapper;
        $this->companyService = $companyService;
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
        $splitJobs = [
            ApprovableStatus::Unapproved->value => [],
            ApprovableStatus::Approved->value => [],
            ApprovableStatus::Rejected->value => [],
        ];
        $company = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany();

        // `packageId` is an optional part of the route and can be used to display jobs specific to that job package. It
        // is null of it was not specified.
        if (null !== ($packageId = $this->params('packageId'))) {
            $package = $this->jobPackageMapper->find($packageId);

            // Check if the package exists and if it belongs to the company of the company user.
            if (
                null === $package
                || $package->getCompany()->getSlugName() !== $company->getSlugName()
            ) {
                return $this->notFoundAction();
            }

            $jobs = $package->getJobs();
            $result['package'] = $package;
        } else {
            // TODO: Get most recent n jobs for each type. Type should also be updated when ORM supports native enums.
            $jobs = [];
            $result['packages'] = $company->getPackages()->filter(function ($package) {
                return 'job' === $package->getType();
            });
        }

        foreach ($jobs as $job) {
            $splitJobs[$job->getApproved()->value][] = $job;
        }

        $result += [
            'unapproved' => $splitJobs[ApprovableStatus::Unapproved->value],
            'approved' => $splitJobs[ApprovableStatus::Approved->value],
            'rejected' => $splitJobs[ApprovableStatus::Rejected->value],
        ];

        return new ViewModel($result);
    }

    public function addJobAction(): ViewModel|Response
    {
        // This is a bit confusing, but to prevent us from having an extra layer of child routes the `packageId` part
        // of the route is not required. However, it is necessary to perform the actions here, hence without it, the
        // route is invalid, and as such we should display an error 404. This applies to the `addJob`, `editJob`, and
        // `deleteJob` actions.
        if (null === ($packageId = $this->params('packageId'))) {
            return $this->notFoundAction();
        }

        if (!$this->aclService->isAllowed('createOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create jobs')
            );
        }

        // Get the specified package and company user (through ACL, as it is already included).
        $package = $this->jobPackageMapper->find($packageId);
        $companySlugName = $this->aclService->getCompanyUserIdentityOrThrowException()->getCompany()->getSlugName();

        // Check if the package exists and if it belongs to the company of the company user.
        if (
            null === $package
            || $package->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
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
                    $this->plugin('FlashMessenger')->addSuccessMessage(
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

    public function editJobAction(): ViewModel
    {
        if (null === ($packageId = $this->params('packageId'))) {
            return $this->notFoundAction();
        }

        if (!$this->aclService->isAllowed('editOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit jobs')
            );
        }

        return new ViewModel([]);
    }

    public function deleteJobAction(): ViewModel
    {
        if (null === ($packageId = $this->params('packageId'))) {
            return $this->notFoundAction();
        }

        if (!$this->aclService->isAllowed('deleteOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete jobs')
            );
        }

        return new ViewModel([]);
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
