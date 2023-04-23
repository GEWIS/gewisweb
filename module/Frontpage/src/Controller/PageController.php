<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Frontpage\Service\Page as PageService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    public function __construct(private readonly PageService $pageService)
    {
    }

    public function pageAction(): ViewModel
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
