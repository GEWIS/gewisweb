<?php

namespace Company\Controller;

use Company\Service\{
    Company as CompanyService,
    CompanyQuery as CompanyQueryService,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;

class CompanyController extends AbstractActionController
{
    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    /**
     * @var CompanyQueryService
     */
    private CompanyQueryService $companyQueryService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * CompanyController constructor.
     *
     * @param CompanyService $companyService
     * @param CompanyQueryService $companyQueryService
     * @param Translator $translator
     */
    public function __construct(
        CompanyService $companyService,
        CompanyQueryService $companyQueryService,
        Translator $translator
    ) {
        $this->companyService = $companyService;
        $this->companyQueryService = $companyQueryService;
        $this->translator = $translator;
    }

    /**
     * Action to display a list of all non-hidden companies.
     */
    public function listAction()
    {
        $featuredPackage = $this->companyService->getFeaturedPackage();

        if (null === $featuredPackage) {
            return new ViewModel(
                [
                    'companyList' => $this->companyService->getCompanyList(),
                ]
            );
        }

        return new ViewModel(
            [
                'companyList' => $this->companyService->getCompanyList(),
                'featuredCompany' => $featuredPackage->getCompany(),
                'featuredPackage' => $featuredPackage,
            ]
        );
    }

    public function showAction()
    {
        $companyName = $this->params('companySlugName');
        $company = $this->companyService->getCompanyBySlugName($companyName);

        if (null !== $company) {
            if (!$company->isHidden()) {
                return new ViewModel(
                    [
                        'company' => $company,
                    ]
                );
            }
        }

        return $this->notFoundAction();
    }

    /**
     * Action that shows the 'company in the spotlight' and the article written by the company in the current language.
     */
    public function spotlightAction()
    {
        $featuredPackage = $this->companyService->getFeaturedPackage();

        if (null !== $featuredPackage) {
            // jobs for a single company
            return new ViewModel(
                [
                    'company' => $featuredPackage->getCompany(),
                    'featuredPackage' => $featuredPackage,
                ]
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
    public function jobListAction()
    {
        $jobCategorySlug = $this->params('category');
        $jobCategory = $this->companyService->getJobCategoryBySlug($jobCategorySlug);

        if (null === $jobCategory) {
            return $this->notFoundAction();
        }

        $viewModel = new ViewModel(
            [
                'jobCategory' => $jobCategory,
            ]
        );

        if ($companySlugName = $this->params('companySlugName', null)) {
            // Retrieve published jobs for one specific company
            $jobs = $this->companyQueryService->getActiveJobList(
                jobCategorySlug: $jobCategorySlug,
                companySlugName: $companySlugName,
            );

            return $viewModel->setVariables(
                [
                    'jobList' => $jobs,
                    'company' => $this->companyService->getCompanyBySlugName($companySlugName),
                ]
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
            ]
        );
    }

    /**
     * Action to list a single job of a certain company.
     */
    public function jobsAction()
    {
        $jobSlugName = $this->params('jobSlugName');
        $companySlugName = $this->params('companySlugName');
        $jobCategorySlug = $this->params('category');

        if (null !== $jobSlugName) {
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
                        ]
                    );
                }
            }

            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'activeJobList' => $this->companyQueryService->getActiveJobList(),
            ]
        );
    }
}
