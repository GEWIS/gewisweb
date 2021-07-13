<?php

namespace Frontpage\Controller;

use Frontpage\Service\News;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;

class NewsAdminController extends AbstractActionController
{
    /**
     * @var News
     */
    private $newsService;

    public function __construct(News $newsService)
    {
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
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->newsService->createNewsItem($request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
            }
        }

        $form = $this->newsService->getNewsItemForm();

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
        $newsItemId = $this->params()->fromRoute('item_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->newsService->updateNewsItem($newsItemId, $request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
            }
        }

        $form = $this->newsService->getNewsItemForm($newsItemId);

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
     */
    public function deleteAction()
    {
        $newsItemId = $this->params()->fromRoute('item_id');
        $this->newsService->deleteNewsItem($newsItemId);
        $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
    }
}
