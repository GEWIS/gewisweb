<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Service\Frontpage\HomePageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontpageController extends AbstractController
{
    public function __construct(
        private readonly HomePageService $homePageService,
    ) {
    }

    #[Route(
        '/',
        name: 'frontpage/index',
    )]
    public function index(): Response
    {
        return $this->render(
            'frontpage/index.html.twig',
            [
                'activities' => [],
                ...$this->homePageService->getHomePageData(),
            ],
        );
    }

    public function notFound(): Response
    {
        throw $this->createNotFoundException();
    }
}
