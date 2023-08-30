<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Application\Model\Enums\Languages;
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
        $language = Languages::from($this->params()->fromRoute('language'));
        $category = $this->params()->fromRoute('category');
        $subCategory = $this->params()->fromRoute('sub_category');
        $name = $this->params()->fromRoute('name');
        $page = $this->pageService->getPage($language, $category, $subCategory, $name);

        if (
            null === $page
            && null !== $subCategory
            && null === $name
        ) {
            $page = $this->pageService->getPage($language, $category, null, $subCategory);
        }

        if (null === $page) {
            return $this->notFoundAction();
        }

        $parents = $this->pageService->getPageParents($page, $language);

        return new ViewModel(
            [
                'page' => $page,
                'parents' => $parents,
            ],
        );
    }
}
