<?php

namespace Frontpage\Controller;

use Frontpage\Service\Frontpage as FrontpageService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class FrontpageController extends AbstractActionController
{
    /**
     * @var FrontpageService
     */
    private FrontpageService $frontpageService;

    /**
     * FrontpageController constructor.
     *
     * @param FrontpageService $frontpageService
     */
    public function __construct(FrontpageService $frontpageService)
    {
        $this->frontpageService = $frontpageService;
    }

    public function homeAction(): ViewModel
    {
        $homePageData = $this->frontpageService->getHomepageData();

        return new ViewModel($homePageData);
    }
}
