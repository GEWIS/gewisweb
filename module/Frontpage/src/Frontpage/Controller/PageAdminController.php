<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PageAdminController extends AbstractActionController
{

    public function indexAction()
    {

    }

    public function createAction()
    {

    }

    public function editAction()
    {

    }

    public function deleteAction()
    {

    }

    /**
     * Get the Page service.
     *
     * @return \Frontpage\Service\Page
     */
    protected function getPageService()
    {
        return $this->getServiceLocator()->get('frontpage_service_page');
    }
}
