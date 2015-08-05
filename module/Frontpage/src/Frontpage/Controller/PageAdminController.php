<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\View;

class PageAdminController extends AbstractActionController
{

    public function indexAction()
    {
        $pages = $this->getPageService()->getPages();

        return new ViewModel(array(
            'pages' => $pages
        ));
    }

    public function createAction()
    {
        $pageService = $this->getPageService();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $page = $pageService->createPage($request->getPost());
            if ($page) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
            }
        }
        $form = $pageService->getPageForm();

        return new ViewModel(array(
            'form' => $form
        ));
    }

    public function editAction()
    {
        $view = new ViewModel(array(
            'form' => $form
        ));

        $view->setTemplate('my-template.phtml');
        return $view;
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
