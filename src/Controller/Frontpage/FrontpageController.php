<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontpageController extends AbstractController
{
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
            ],
        );
    }

    public function notFound(): Response
    {
        throw $this->createNotFoundException();
    }
}
