<?php

namespace Frontpage\Controller;

use Frontpage\Service\Frontpage;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class FrontpageController extends AbstractActionController
{
    /**
     * @var Frontpage
     */
    private $frontpageService;

    public function __construct(Frontpage $frontpageService)
    {
        $this->frontpageService = $frontpageService;
    }

    public function homeAction()
    {
        $homePageData = $this->frontpageService->getHomepageData();

        return new ViewModel($homePageData);
    }
}
