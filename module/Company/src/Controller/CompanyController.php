<?php

declare(strict_types=1);

namespace Company\Controller;

use Company\Service\Company as CompanyService;
use Company\Service\CompanyQuery as CompanyQueryService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

use function shuffle;

class CompanyController extends AbstractActionController
{
    public function __construct(
        private readonly CompanyService $companyService,
        private readonly CompanyQueryService $companyQueryService,
    ) {
    }

    /**
     * Action to display a list of all non-hidden companies.
     */
    public function listAction(): ViewModel
    {
        $featuredPackage = $this->companyService->getFeaturedPackage();

        if (null === $featuredPackage) {
            return new ViewModel(
                [
                    'companyList' => $this->companyService->getCompanyList(),
                ],
            );
        }

        return new ViewModel(
            [
                'companyList' => $this->companyService->getCompanyList(),
                'featuredCompany' => $featuredPackage->getCompany(),
                'featuredPackage' => $featuredPackage,
            ],
        );
    }

    public function showAction(): ViewModel
    {
        $companyName = $this->params()->fromRoute('companySlugName');
        $company = $this->companyService->getCompanyBySlugName($companyName);

        if (null !== $company) {
            if (!$company->isHidden()) {
                return new ViewModel(
                    [
                        'company' => $company,
                    ],
                );
            }
        }

        return $this->notFoundAction();
    }

    /**
     * Action that shows the 'company in the spotlight' and the article written by the company in the current language.
     */
    public function spotlightAction(): ViewModel
    {
        $featuredPackage = $this->companyService->getFeaturedPackage();

        if (null !== $featuredPackage) {
            // jobs for a single company
            return new ViewModel(
                [
                    'company' => $featuredPackage->getCompany(),
                    'featuredPackage' => $featuredPackage,
                ],
            );
        }

        // There is no company is the spotlight, so throw a 404
        return $this->notFoundAction();
    }

    /**
     * Action that displays a list of all jobs or a list of jobs for a company.
     *
     * TODO: If the slug in the current locale is different from the slug parameter, redirect.
     */
    public function jobListAction(): ViewModel
    {
        $jobCategorySlug = $this->params()->fromRoute('category');
        $jobCategory = $this->companyService->getJobCategoryBySlug($jobCategorySlug);

        if (null === $jobCategory) {
            return $this->notFoundAction();
        }

        $viewModel = new ViewModel(
            [
                'jobCategory' => $jobCategory,
            ],
        );

        if (null !== ($companySlugName = $this->params()->fromRoute('companySlugName'))) {
            // Retrieve published jobs for one specific company
            $jobs = $this->companyQueryService->getActiveJobList(
                jobCategorySlug: $jobCategorySlug,
                companySlugName: $companySlugName,
            );

            return $viewModel->setVariables(
                [
                    'jobList' => $jobs,
                    'company' => $this->companyService->getCompanyBySlugName($companySlugName),
                ],
            );
        }

        // Retrieve all published jobs
        $jobs = $this->companyQueryService->getActiveJobList(
            jobCategorySlug: $jobCategorySlug,
        );

        // Shuffle order to avoid bias
        shuffle($jobs);

        return $viewModel->setVariables(
            [
                'jobList' => $jobs,
            ],
        );
    }

    /**
     * Action to list a single job of a certain company.
     */
    public function jobsAction(): ViewModel
    {
        $jobSlugName = $this->params()->fromRoute('jobSlugName');
        $companySlugName = $this->params()->fromRoute('companySlugName');
        $jobCategorySlug = $this->params()->fromRoute('category');

        $jobs = $this->companyQueryService->getJobs(
            jobCategorySlug: $jobCategorySlug,
            jobSlugName: $jobSlugName,
            companySlugName: $companySlugName,
        );

        if (!empty($jobs)) {
            if ($jobs[0]->isActive()) {
                return new ViewModel(
                    [
                        'job' => $jobs[0],
                    ],
                );
            }
        }

        return $this->notFoundAction();
    }
}
