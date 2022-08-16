<?php

namespace Frontpage\Controller;

use Exception;
use Frontpage\Service\Page as PageService;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};

class PageAdminController extends AbstractActionController
{
    public function __construct(private readonly PageService $pageService)
    {
    }

    public function indexAction(): ViewModel
    {
        $pages = $this->pageService->getPages();

        return new ViewModel(
            [
                'pages' => $pages,
            ]
        );
    }

    public function createAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->pageService->createPage($request->getPost())) {
                return $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
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

    public function editAction(): Response|ViewModel
    {
        $pageId = $this->params()->fromRoute('page_id');
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->pageService->updatePage($pageId, $request->getPost())) {
                return $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
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

    public function deleteAction(): Response
    {
        $pageId = $this->params()->fromRoute('page_id');
        $this->pageService->deletePage($pageId);

        return $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
    }

    public function uploadAction(): JsonModel
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];
        $result['uploaded'] = 0;

        if ($request->isPost()) {
            try {
                $path = $this->pageService->uploadImage($request->getFiles());
                $result['url'] = '/' . $path;
                $result['fileName'] = $path;
                $result['uploaded'] = 1;
            } catch (Exception $e) {
                $result['error']['message'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
    }
}
