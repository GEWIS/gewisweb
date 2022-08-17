<?php

namespace Frontpage\Controller;

use Exception;
use Frontpage\Service\{
    AclService,
    Page as PageService,
};
use Laminas\Mvc\I18n\Translator;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use User\Permissions\NotAllowedException;

class PageAdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PageService $pageService,
    ) {
    }

    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('list', 'page')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of pages.')
            );
        }

        return new ViewModel(
            [
                'pages' => $this->pageService->getPages(),
            ]
        );
    }

    public function createAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'page')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create new pages.')
            );
        }

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
        if (!$this->aclService->isAllowed('edit', 'page')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit pages.'));
        }

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
        if (!$this->aclService->isAllowed('delete', 'page')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete pages.'));
        }

        $pageId = $this->params()->fromRoute('page_id');
        $this->pageService->deletePage($pageId);

        return $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
    }

    public function uploadAction(): JsonModel
    {
        if (
            !$this->aclService->isAllowed('create', 'page')
            && !$this->aclService->isAllowed('edit', 'page')
            && !$this->aclService->isAllowed('create', 'news_item')
            && !$this->aclService->isAllowed('edit', 'news_item')
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload images.'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];
        $result['uploaded'] = 0;

        if ($request->isPost()) {
            try {
                $path = $this->pageService->uploadImage($request->getFiles()->toArray());
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
