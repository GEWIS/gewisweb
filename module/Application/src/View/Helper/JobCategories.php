<?php

namespace Application\View\Helper;

use Company\Service\CompanyQuery;
use Laminas\View\Helper\AbstractHelper;

class JobCategories extends AbstractHelper
{
    /**
     * @var CompanyQuery
     */
    private $companyQueryService;

    public function __construct(CompanyQuery $companyQueryService)
    {
        $this->companyQueryService = $companyQueryService;
    }

    /**
     * Returns all visible categories.
     *
     * @return array
     */
    public function __invoke()
    {
        return $this->companyQueryService->getCategoryList(true);
    }
}
