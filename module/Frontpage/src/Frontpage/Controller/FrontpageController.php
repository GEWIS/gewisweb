<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class FrontpageController extends AbstractActionController
{
    public function homeAction()
    {
        return new ViewModel($this->getFrontpageService()->getHomepageData());
    }

    /**
     * Get the frontpage service.
     *
     * @return \Frontpage\Service\Frontpage
     */
    protected function getFrontpageService()
    {
        return $this->getServiceLocator()->get('frontpage_service_frontpage');
    }
}
