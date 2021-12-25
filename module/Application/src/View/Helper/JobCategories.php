<?php

namespace Application\View\Helper;

use Company\Service\CompanyQuery as CompanyQueryService;
use Laminas\View\Helper\AbstractHelper;

class JobCategories extends AbstractHelper
{
    /**
     * @var CompanyQueryService
     */
    private CompanyQueryService $companyQueryService;

    /**
     * @param CompanyQueryService $companyQueryService
     */
    public function __construct(CompanyQueryService $companyQueryService)
    {
        $this->companyQueryService = $companyQueryService;
    }

    /**
     * Returns all visible categories.
     *
     * @return array
     */
    public function __invoke(): array
    {
        return $this->companyQueryService->getCategoryList(true);
    }
}
