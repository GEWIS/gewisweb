<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Frontpage\Service\AclService;
use Frontpage\Service\Page as PageService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Throwable;
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
                $this->translator->translate('You are not allowed to view the list of pages.'),
            );
        }

        return new ViewModel(
            [
                'pages' => $this->pageService->getPages(),
            ],
        );
    }

    public function createAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'page')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create new pages.'),
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
            ],
        );

        $view->setTemplate('page-admin/edit');

        return $view;
    }

    public function editAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'page')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit pages'));
        }

        $pageId = (int) $this->params()->fromRoute('page_id');
        $page = $this->pageService->getPageById($pageId);

        if (null === $page) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->pageService->getPageForm();
        $form->setData($page->toArray());

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            $form->setCurrentValues(
                $page->getCategory(),
                $page->getSubCategory(),
                $page->getName(),
            );

            if ($form->isValid()) {
                if ($this->pageService->updatePage($page, $form->getData())) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
                'canDelete' => true,
                'pageId' => $pageId,
            ],
        );
    }

    public function deleteAction(): Response
    {
        if (!$this->aclService->isAllowed('delete', 'page')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete pages'));
        }

        $pageId = (int) $this->params()->fromRoute('page_id');
        $this->pageService->deletePage($pageId);

        return $this->redirect()->toUrl($this->url()->fromRoute('admin_page'));
    }

    public function uploadAction(): JsonModel
    {
        if (
            !$this->aclService->isAllowed('create', 'page')
            && !$this->aclService->isAllowed('edit', 'page')
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload images'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            try {
                $path = $this->pageService->uploadImage($request->getFiles()->toArray());
                $result['url'] = '/' . $path;
            } catch (Throwable $e) {
                $result['error']['message'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
    }
}
