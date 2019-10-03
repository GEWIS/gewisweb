<?php

namespace Frontpage\Controller;

use Frontpage\Service\Frontpage;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

class FrontpageController extends AbstractActionController
{
    public function homeAction()
    {
        $homePageData = $this->getFrontpageService()->getHomepageData();
        return new ViewModel($homePageData);
    }

    /**
     * Get the frontpage service.
     *
     * @return Frontpage
     */
    protected function getFrontpageService()
    {
        return $this->getServiceLocator()->get('frontpage_service_frontpage');
    }
}
