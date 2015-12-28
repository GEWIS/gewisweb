<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Paginator\Paginator;


class NewsAdminController extends AbstractActionController
{

    public function listAction()
    {
        $newsService = $this->getNewsService();

        $adapter = $newsService->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(15);

        $page = $this->params()->fromRoute('page');

        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        return new ViewModel([
            'paginator' => $paginator,
        ]);
    }

    /**
     * Create a news item
     */
    public function createAction()
    {
        $newsService = $this->getNewsService();
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($newsService->createNewsItem($request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
            }
        }

        $form = $newsService->getNewsItemForm();

        $view = new ViewModel([
            'form' => $form,
            // Boolean indicating if the view should show an option to delete a news item.
            'canDelete' => false
        ]);

        $view->setTemplate('news-admin/edit');

        return $view;
    }

    /**
     * Edit a news item
     */
    public function editAction()
    {
        $newsService = $this->getNewsService();
        $newsItemId = $this->params()->fromRoute('item_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($newsService->updateNewsItem($newsItemId, $request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
            }
        }

        $form = $newsService->getNewsItemForm($newsItemId);

        return new ViewModel([
            'form' => $form,
            // Boolean indicating if the view should show an option to delete a news item.
            'canDelete' => true,
            'newsItemId' => $newsItemId
        ]);
    }

    /**
     * Delete a news item
     */
    public function deleteAction()
    {
        $newsItemId = $this->params()->fromRoute('item_id');
        $this->getNewsService()->deleteNewsItem($newsItemId);
        $this->redirect()->toUrl($this->url()->fromRoute('admin_news'));
    }

    /**
     * Get the News service.
     *
     * @return \Frontpage\Service\News
     */
    protected function getNewsService()
    {
        return $this->getServiceLocator()->get('frontpage_service_news');
    }
}
