<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;


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
            'form' => $form,
            // Boolean indicating if the view should show an option to delete a page.
            'canDelete' => false
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
            'form' => $form,
            'canDelete' => true,
            'pageId' => $pageId
        ));
    }

    public function deleteAction()
    {
        $pageId = $this->params()->fromRoute('page_id');
        $this->getPageService()->deletePage($pageId);
        $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
    }

    public function uploadAction()
    {
        $request = $this->getRequest();
        $result = array();
        $result['uploaded'] = 0;
        if ($request->isPost()) {
            try {
                $path = $this->getPageService()->uploadImage($request->getFiles());
                $result['url'] = $request->getBasePath() . '/' . $path;
                $result['fileName'] = $path;
                $result['uploaded'] = 1;
            } catch (\Exception $e) {
                $result['error']['message'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
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
