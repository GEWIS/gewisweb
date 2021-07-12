<?php

namespace Application\View\Helper;

use Company\Model\CompanyFeaturedPackage;
use Company\Service\Company;
use Zend\View\Helper\AbstractHelper;

class FeaturedCompanyPackage extends AbstractHelper
{
    /**
     * @var Company
     */
    private $companyService;

    public function __construct(Company $companyService)
    {

        $this->companyService = $companyService;
    }
    /**
     * Returns currently active featurePackage
     *
     * @return CompanyFeaturedPackage
     */
    public function __invoke()
    {
        return $this->companyService->getFeaturedPackage();
    }
}
