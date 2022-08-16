<?php

namespace Frontpage\Controller;

use Frontpage\Service\Frontpage as FrontpageService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class FrontpageController extends AbstractActionController
{
    public function __construct(private readonly FrontpageService $frontpageService)
    {
    }

    public function homeAction(): ViewModel
    {
        $homePageData = $this->frontpageService->getHomepageData();

        return new ViewModel($homePageData);
    }
}
