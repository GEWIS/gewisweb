<?php

namespace Application\View\Helper;

use Company\Model\CompanyFeaturedPackage as CompanyFeaturedPackageModel;
use Company\Service\Company as CompanyService;
use Laminas\View\Helper\AbstractHelper;

class FeaturedCompanyPackage extends AbstractHelper
{
    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    /**
     * @param CompanyService $companyService
     */
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Returns currently active featurePackage.
     *
     * @return CompanyFeaturedPackageModel|null
     */
    public function __invoke(): ?CompanyFeaturedPackageModel
    {
        return $this->companyService->getFeaturedPackage();
    }
}
