<?php

namespace Application\View\Helper;

use Company\Service\CompanyQuery as CompanyQueryService;
use Laminas\View\Helper\AbstractHelper;

class JobCategories extends AbstractHelper
{
    public function __construct(private readonly CompanyQueryService $companyQueryService)
    {
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
