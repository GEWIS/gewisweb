<?php

namespace Company\Controller;

use Company\Service\{
    AclService,
    Company as CompanyService,
    CompanyQuery as CompanyQueryService,
};
use Company\Model\CompanyJobPackage;
use DateInterval;
use DateTime;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class AdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly CompanyService $companyService,
        private readonly CompanyQueryService $companyQueryService,
    ) {
    }

    /**
     * Action that displays the main page.
     */
    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to administer career settings')
            );
        }

        // Initialize the view
        return new ViewModel(
            [
                'companyList' => $this->companyService->getHiddenCompanyList(),
                'categoryList' => $this->companyQueryService->getCategoryList(false),
                'labelList' => $this->companyQueryService->getLabelList(false),
                'packageFuture' => $this->companyService->getPackageChangeEvents(
                    (new DateTime())->add(
                        new DateInterval('P1M')
                    )
                ),
            ]
        );
    }

    /**
     * Action that allows adding a company.
     */
    public function addCompanyAction(): Response|array
    {
        if (!$this->aclService->isAllowed('create', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create companies'));
        }

        $form = $this->companyService->getCompanyForm();

        // Handle incoming form results
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $form->setData($post);
            if ($form->isValid()) {
                $company = $this->companyService->createCompany($form->getData());

                if (false !== $company) {
                    // Redirect to edit page
                    return $this->redirect()->toRoute(
                        'company_admin/company/edit',
                        [
                            'companySlugName' => $company->getSlugName(),
                        ],
                    );
                }
            }
        }

        return [
            'form' => $form,
        ];
    }

    /**
     * Action that displays a form for editing a company.
     */
    public function editCompanyAction(): Response|array|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit companies'));
        }

        // Get useful stuff
        $companyForm = $this->companyService->getCompanyForm();

        // Get parameter
        $companySlugName = $this->params()->fromRoute('companySlugName');

        // Get the specified company
        $company = $this->companyService->getCompanyBySlugName($companySlugName);

        // If the company is not found, throw 404
        if (null === $company) {
            return $this->notFoundAction();
        }

        // Handle incoming form data
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $companyForm->setData($post);
            $companyForm->setCurrentSlug($companySlugName);

            if ($companyForm->isValid()) {
                if (
                    $this->companyService->updateCompany(
                        $company,
                        $companyForm->getData(),
                    )
                ) {
                    $slugName = $post['slugName'];

                    return $this->redirect()->toRoute(
                        'company_admin/company/edit',
                        [
                            'companySlugName' => $slugName,
                        ],
                    );
                }
            }
        }

        $companyData = $company->toArray();
        $companyData['language_dutch'] = null !== $companyData['description'];
        $companyData['language_english'] = null !== $companyData['descriptionEn'];
        $companyForm->setData($companyData);

        return [
            'company' => $company,
            'form' => $companyForm,
        ];
    }

    /**
     * Action that first asks for confirmation, and when given, deletes the company.
     */
    public function deleteCompanyAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('delete', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete companies'));
        }

        // Handle incoming form data
        /** @var Request $request */
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        // Get parameters
        $slugName = $this->params()->fromRoute('companySlugName');

        $this->companyService->deleteCompanyBySlug($slugName);

        return $this->redirect()->toRoute('company_admin');
    }

    /**
     * Action that allows adding a package.
     */
    public function addPackageAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create packages'));
        }

        // Get parameter
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $type = $this->params()->fromRoute('type');
        $company = $this->companyService->getCompanyBySlugName($companySlugName);

        if (null === $company) {
            return $this->notFoundAction();
        }

        // Get form
        $packageForm = $this->companyService->getPackageForm($type);

        // Handle incoming form results
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $packageForm->setData($post);

            if ($packageForm->isValid()) {
                if ($this->companyService->createPackage($company, $packageForm->getData(), $type)) {
                    // Redirect to edit page
                    return $this->redirect()->toRoute(
                        'company_admin/company/edit',
                        [
                            'companySlugName' => $companySlugName,
                        ],
                    );
                }
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'company_admin/company/edit/package/add',
                [
                    'companySlugName' => $companySlugName,
                    'type' => $type,
                ],
            )
        );

        // Initialize the view
        return new ViewModel(
            [
                'form' => $packageForm,
                'type' => $type,
            ]
        );
    }

    /**
     * Action that displays a form for editing a package.
     */
    public function editPackageAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit packages'));
        }

        // Get the parameters
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');

        // Get the specified package
        $package = $this->companyService->getPackageById($packageId);

        // Check whether the package exists, and it is actually bound to this company.
        if (
            null === $package
            || $package->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        // Get form
        $type = $package->getType();
        $packageForm = $this->companyService->getPackageForm($type);

        // Handle incoming form results
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $packageForm->setData($post);

            if ($packageForm->isValid()) {
                if ($this->companyService->updatePackage($package, $packageForm->getData())) {
                    // Redirect to edit page
                    return $this->redirect()->toRoute(
                        'company_admin/company/edit/package/edit',
                        [
                            'companySlugName' => $companySlugName,
                            'packageId' => $packageId,
                        ],
                    );
                }
            }
        }

        // Initialize form
        $packageData = $package->toArray();

        if ('featured' === $type) {
            $packageData['language_dutch'] = null !== $packageData['article'];
            $packageData['language_english'] = null !== $packageData['articleEn'];
        }

        $packageForm->setData($packageData);
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'company_admin/company/edit/package/edit',
                [
                    'companySlugName' => $companySlugName,
                    'packageId' => $packageId,
                ]
            )
        );

        // Initialize the view
        return new ViewModel(
            [
                'package' => $package,
                'companySlugName' => $companySlugName,
                'form' => $packageForm,
                'type' => $type,
            ]
        );
    }

    /**
     * Action that first asks for confirmation, and when given, deletes the Package.
     */
    public function deletePackageAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('delete', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete packages'));
        }

        // Handle incoming form data
        /** @var Request $request */
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        // Get the parameters
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');

        // Get the specified package
        $package = $this->companyService->getPackageById($packageId);

        // Check whether the package exists, and it is actually bound to this company.
        if (
            null === $package
            || $package->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        $this->companyService->deletePackage($package);

        return $this->redirect()->toRoute(
            'company_admin/company/edit',
            [
                'companySlugName' => $companySlugName,
            ],
        );
    }

    /**
     * Action that allows adding a job.
     */
    public function addJobAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'job')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create jobs'));
        }

        // Get parameters
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');

        // Get the specified package
        $package = $this->companyService->getPackageById($packageId);

        // Check if if the package exists, if it is part of the current company, and it is of the job type.
        if (
            null === $package
            || $package->getCompany()->getSlugName() !== $companySlugName
            || !($package instanceof CompanyJobPackage)
        ) {
            return $this->notFoundAction();
        }

        // Get useful stuff
        $jobForm = $this->companyService->getJobForm();

        // Handle incoming form results
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );

            $jobForm->setData($post);
            $jobForm->setCompanySlug($companySlugName);

            // Check if data is valid, and insert when it is
            if ($jobForm->isValid()) {
                if (false !== $this->companyService->createJob($package, $jobForm->getData())) {
                    return $this->redirect()->toRoute(
                        'company_admin/company/edit/package/edit',
                        [
                            'companySlugName' => $companySlugName,
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
                'company_admin/company/edit/package/edit/job/add',
                [
                    'companySlugName' => $companySlugName,
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

    /**
     * Action that displays a form for editing a job.
     */
    public function editJobAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'job')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit jobs'));
        }

        // Get parameters
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');

        // Find the specified jobs
        $job = $this->companyService->getJobById($jobId);

        // Check the job is found. If not, throw 404
        if (
            null === $job
            || $job->getPackage()->getId() !== $packageId
            || $job->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        // Get useful stuff
        $jobForm = $this->companyService->getJobForm();

        // Handle incoming form results
        /** @var Request $request */
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
                if ($this->companyService->updateJob($job, $jobForm->getData())) {
                    return $this->redirect()->toRoute(
                        'company_admin/company/edit/package/edit',
                        [
                            'companySlugName' => $companySlugName,
                            'packageId' => $packageId,
                        ]
                    );
                }
            }
        }

        // Initialize the form
        $jobData = $job->toArray();
        $jobData['language_dutch'] = null !== $jobData['description'];
        $jobData['language_english'] = null !== $jobData['descriptionEn'];
        $jobData['category'] = $job->getCategory()->getId();
        $jobForm->setData($jobData);

        // Initialize the view
        return new ViewModel(
            [
                'form' => $jobForm,
                'attachments' => $job->getAttachment(),
            ]
        );
    }

    /**
     * Action to delete a job.
     */
    public function deleteJobAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('delete', 'job')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete jobs'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        // Get parameters
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $packageId = (int) $this->params()->fromRoute('packageId');
        $jobId = (int) $this->params()->fromRoute('jobId');

        // Find the specified jobs
        $job = $this->companyService->getJobById($jobId);

        // Check the job is found. If not, throw 404
        if (
            null === $job
            || $job->getPackage()->getId() !== $packageId
            || $job->getCompany()->getSlugName() !== $companySlugName
        ) {
            return $this->notFoundAction();
        }

        $this->companyService->deleteJob($job);

        // Redirect to package page
        return $this->redirect()->toRoute(
            'company_admin/company/edit/package/edit',
            [
                'companySlugName' => $companySlugName,
                'packageId' => $packageId,
            ],
        );
    }

    public function addCategoryAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'jobCategory')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create job categories'));
        }

        // Get useful stuff
        $categoryForm = $this->companyService->getCategoryForm();

        // Handle incoming form results
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $categoryForm->setData($request->getPost()->toArray());

            if ($categoryForm->isValid()) {
                $jobCategory = $this->companyService->createJobCategory($categoryForm->getData());

                if (is_object($jobCategory)) {
                    // Redirect to edit page
                    return $this->redirect()->toRoute(
                        'company_admin/category/edit',
                        [
                            'jobCategoryId' => $jobCategory->getId(),
                        ],
                    );
                }
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $categoryForm->setAttribute(
            'action',
            $this->url()->fromRoute('company_admin/category/add'),
        );
        // Initialize the view
        return new ViewModel(
            [
                'form' => $categoryForm,
            ]
        );
    }

    /**
     * Action that displays a form for editing a category.
     */
    public function editCategoryAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'jobCategory')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit job categories'));
        }

        // Get parameter
        $jobCategoryId = (int) $this->params()->fromRoute('jobCategoryId');

        // Get the specified category
        $jobCategory = $this->companyService->getJobCategoryById($jobCategoryId);

        // If the category is not found, throw 404
        if (null === $jobCategory) {
            return $this->notFoundAction();
        }

        // Get useful stuff
        $categoryForm = $this->companyService->getCategoryForm();

        // Handle incoming form data
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $categoryForm->setData($request->getPost()->toArray());
            $categoryForm->setCurrentSlug($jobCategory->getPluralName());

            if ($categoryForm->isValid()) {
                $this->companyService->updateJobCategory($jobCategory, $categoryForm->getData());
            }
        }

        // Initialize form
        $jobCategoryData = $jobCategory->toArray();
        $categoryForm->setData($jobCategoryData);

        return new ViewModel(['form' => $categoryForm]);
    }

    public function addLabelAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'jobLabel')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create job labels'));
        }

        // Get useful stuff
        $labelForm = $this->companyService->getLabelForm();

        // Handle incoming form results
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $labelForm->setData($request->getPost()->toArray());

            if ($labelForm->isValid()) {
                $jobLabel = $this->companyService->createJobLabel($labelForm->getData());

                if (is_object($jobLabel)) {
                    // Redirect to edit page
                    return $this->redirect()->toRoute(
                        'company_admin/label/edit',
                        [
                            'jobLabelId' => $jobLabel->getId(),
                        ],
                    );
                }
            }
        }

        // Initialize the form
        $labelForm->setAttribute(
            'action',
            $this->url()->fromRoute('company_admin/label/add'),
        );

        // Initialize the view
        return new ViewModel(
            [
                'form' => $labelForm,
            ]
        );
    }

    /**
     * Action that displays a form for editing a label.
     */
    public function editLabelAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'jobLabel')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit job labels'));
        }

        // Get parameter
        $jobLabelId = (int) $this->params()->fromRoute('jobLabelId');

        // Get the specified label
        $jobLabel = $this->companyService->getJobLabelById($jobLabelId);

        // If the label is not found, throw 404
        if (null === $jobLabel) {
            return $this->notFoundAction();
        }

        // Get useful stuff
        $labelForm = $this->companyService->getLabelForm();

        // Handle incoming form data
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $labelForm->setData($request->getPost()->toArray());

            if ($labelForm->isValid()) {
                $this->companyService->updateJobLabel($jobLabel, $labelForm->getData());
            }
        }

        // Initialize form
        $jobLabelData = $jobLabel->toArray();
        $jobLabelData['language_dutch'] = null !== $jobLabelData['name'];
        $jobLabelData['language_english'] = null !== $jobLabelData['nameEn'];
        $labelForm->setData($jobLabelData);

        return new ViewModel(['form' => $labelForm]);
    }
}
