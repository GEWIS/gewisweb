<?php

namespace Frontpage\Controller;

use Frontpage\Service\{
    AclService,
    News as NewsService,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class NewsAdminController extends AbstractActionController
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var NewsService
     */
    private NewsService $newsService;

    /**
     * NewsAdminController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param NewsService $newsService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        NewsService $newsService,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->newsService = $newsService;
    }

    public function listAction()
    {
        $adapter = $this->newsService->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(15);

        $page = $this->params()->fromRoute('page');

        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        return new ViewModel(
            [
                'paginator' => $paginator,
            ]
        );
    }

    /**
     * Create a news item.
     */
    public function createAction()
    {
        if (!$this->aclService->isAllowed('create', 'news_item')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create news items'));
        }

        $form = $this->newsService->getNewsItemForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->newsService->createNewsItem($form->getData())) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
                }
            }
        }

        $view = new ViewModel(
            [
                'form' => $form,
                // Boolean indicating if the view should show an option to delete a news item.
                'canDelete' => false,
            ]
        );

        $view->setTemplate('news-admin/edit');

        return $view;
    }

    /**
     * Edit a news item.
     */
    public function editAction()
    {
        if (!$this->aclService->isAllowed('edit', 'news_item')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit news items'));
        }

        $newsItemId = $this->params()->fromRoute('item_id');
        $newsItem = $this->newsService->getNewsItemById($newsItemId);

        if (null === $newsItem) {
            return $this->notFoundAction();
        }

        $form = $this->newsService->getNewsItemForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->newsService->updateNewsItem($newsItem, $form->getData())) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
                }
            }
        }

        $form->bind($newsItem);

        return new ViewModel(
            [
                'form' => $form,
                // Boolean indicating if the view should show an option to delete a news item.
                'canDelete' => true,
                'newsItemId' => $newsItemId,
            ]
        );
    }

    /**
     * Delete a news item.
     *
     * TODO: Non-idempotent requests should be done via POST, not GET.
     */
    public function deleteAction()
    {
        if (!$this->aclService->isAllowed('delete', 'news_item')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete news items'));
        }

        $newsItemId = $this->params()->fromRoute('item_id');
        $newsItem = $this->newsService->getNewsItemById($newsItemId);

        if (null === $newsItem) {
            return $this->notFoundAction();
        }

        $this->newsService->deleteNewsItem($newsItem);
        $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
    }
}
