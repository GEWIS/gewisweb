<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    public function pageAction()
    {
        $category = $this->params()->fromRoute('category');
        $subCategory = $this->params()->fromRoute('sub_category');
        $name = $this->params()->fromRoute('name');
        $page = $this->getPageService()->getPage($category, $subCategory, $name);

        if(is_null($page)) {
            return $this->notFoundAction();
        }

        $session = new SessionContainer('lang');

        return new ViewModel(array(
            'page' => $page,
            'lang' => $session->lang
        ));
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
