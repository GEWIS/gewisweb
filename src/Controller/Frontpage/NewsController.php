<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use App\Repository\Frontpage\NewsItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

use function in_array;
use function strval;

/**
 * The news as anybody reads it: everything the association has put out, narrowed to one part of it if the reader
 * says so, and a page per item.
 *
 * The archive picks its year and its category from the query string rather than the path, so an item keeps the
 * shortest address there is.
 */
#[Route(
    path: '/news',
    name: 'news/',
)]
class NewsController extends AbstractController
{
    public function __construct(private readonly NewsItemRepository $newsItemRepository)
    {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(
        #[MapQueryParameter]
        ?int $year = null,
        #[MapQueryParameter]
        ?string $category = null,
    ): Response {
        $years = $this->newsItemRepository->findYears();

        return $this->render(
            'frontpage/news/index.html.twig',
            [
                // A year nothing was written in reads as the whole archive, which is what a stale bookmark deserves.
                'year' => in_array(
                    $year,
                    $years,
                    true,
                ) ? $year : null,
                'years' => $years,
                // Likewise a category nobody has heard of.
                'category' => NewsCategory::tryFrom(strval($category))?->value,
            ],
        );
    }

    #[Route(
        path: '/{item}',
        name: 'view',
        requirements: ['item' => '\d+'],
    )]
    public function view(NewsItem $item): Response
    {
        return $this->render(
            'frontpage/news/view.html.twig',
            ['item' => $item],
        );
    }
}
