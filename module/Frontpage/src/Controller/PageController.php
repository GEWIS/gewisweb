<?php

namespace Frontpage\Controller;

use Frontpage\Service\Page as PageService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    /**
     * @var PageService
     */
    private PageService $pageService;

    /**
     * PageController constructor.
     *
     * @param PageService $pageService
     */
    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function pageAction()
    {
        $category = $this->params()->fromRoute('category');
        $subCategory = $this->params()->fromRoute('sub_category');
        $name = $this->params()->fromRoute('name');
        $page = $this->pageService->getPage($category, $subCategory, $name);

        if (is_null($page)) {
            return $this->notFoundAction();
        }

        $parents = $this->pageService->getPageParents($page);

        return new ViewModel(
            [
                'page' => $page,
                'parents' => $parents,
            ]
        );
    }
}
