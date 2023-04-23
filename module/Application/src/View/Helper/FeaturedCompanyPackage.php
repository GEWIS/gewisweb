<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Company\Model\CompanyFeaturedPackage as CompanyFeaturedPackageModel;
use Company\Service\Company as CompanyService;
use Laminas\View\Helper\AbstractHelper;

class FeaturedCompanyPackage extends AbstractHelper
{
    public function __construct(private readonly CompanyService $companyService)
    {
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
