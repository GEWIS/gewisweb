<?php

namespace Frontpage\Controller;

use Exception;
use Frontpage\Service\Page;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class PageAdminController extends AbstractActionController
{
    /**
     * @var Page
     */
    private $pageService;

    public function __construct(Page $pageService)
    {
        $this->pageService = $pageService;
    }

    public function indexAction()
    {
        $pages = $this->pageService->getPages();

        return new ViewModel(
            [
            'pages' => $pages,
            ]
        );
    }

    public function createAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->pageService->createPage($request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
            }
        }

        $form = $this->pageService->getPageForm();

        $view = new ViewModel(
            [
            'form' => $form,
            // Boolean indicating if the view should show an option to delete a page.
            'canDelete' => false,
            ]
        );

        $view->setTemplate('page-admin/edit');

        return $view;
    }

    public function editAction()
    {
        $pageId = $this->params()->fromRoute('page_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->pageService->updatePage($pageId, $request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
            }
        }

        $form = $this->pageService->getPageForm($pageId);

        return new ViewModel(
            [
            'form' => $form,
            'canDelete' => true,
            'pageId' => $pageId,
            ]
        );
    }

    public function deleteAction()
    {
        $pageId = $this->params()->fromRoute('page_id');
        $this->pageService->deletePage($pageId);
        $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
    }

    public function uploadAction()
    {
        $request = $this->getRequest();
        $result = [];
        $result['uploaded'] = 0;
        if ($request->isPost()) {
            try {
                $path = $this->pageService->uploadImage($request->getFiles());
                $result['url'] = $request->getBasePath().'/'.$path;
                $result['fileName'] = $path;
                $result['uploaded'] = 1;
            } catch (Exception $e) {
                $result['error']['message'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
    }
}
