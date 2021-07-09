<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

class FrontpageController extends AbstractActionController
{

    /**
     * @var \Frontpage\Service\Frontpage
     */
    private $frontpageService;

    public function __construct(\Frontpage\Service\Frontpage $frontpageService)
    {
        $this->frontpageService = $frontpageService;
    }

    public function homeAction()
    {
        $homePageData = $this->frontpageService->getHomepageData();
        return new ViewModel($homePageData);
    }
}
