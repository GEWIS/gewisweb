<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
            if ($pageService->createPage($request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
            }
        }

        $form = $pageService->getPageForm();

        $view = new ViewModel(array(
            'form' => $form
        ));

        $view->setTemplate('page-admin/edit');

        return $view;
    }

    public function editAction()
    {
        $pageService = $this->getPageService();
        $pageId = $this->params()->fromRoute('page_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($pageService->updatePage($pageId, $request->getPost())) {
                    $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
            }
        }

        $form = $pageService->getPageForm($pageId);

        return new ViewModel(array(
            'form' => $form
        ));
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
