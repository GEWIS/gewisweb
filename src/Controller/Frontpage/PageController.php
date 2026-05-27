<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Entity\Application\Enums\Languages;
use App\Service\Frontpage\PageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends AbstractController
{
    public function __construct(private readonly PageService $pageService)
    {
    }

    public function index(
        Request $request,
        string $category,
        ?string $subCategory = null,
        ?string $name = null,
    ): Response {
        $lang = Languages::fromLangParam($request->getLocale());
        $page = $this->pageService->getPage(
            $lang,
            $category,
            $subCategory,
            $name,
        );

        if (
            null === $page
            && null !== $subCategory
            && null === $name
        ) {
            $page = $this->pageService->getPage(
                $lang,
                $category,
                null,
                $subCategory,
            );
        }

        if (null === $page) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted($page->getRequiredRole()->value);

        return $this->render(
            'frontpage/page.html.twig',
            [
                'page' => $page,
                'parents' => $this->pageService->getPageParents(
                    $page,
                    $lang,
                ),
            ],
        );
    }
}
