<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/activities',
    name: 'activity/',
)]
class ActivityController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    #[Route(
        path: '/{category}',
        name: 'category',
        requirements: ['category' => '[a-z][0-9a-z\_\-]{2,31}'],
        defaults: ['category' => null],
    )]
    public function index(?string $category = null): Response
    {
        return $this->render('activity/index.html.twig');
    }

    #[Route(
        path: '/my',
        name: 'my',
    )]
    public function my(): Response
    {
        return $this->render('activity/my.html.twig');
    }

    #[Route(
        path: '/archive/{year}',
        name: 'archive',
        requirements: ['year' => '\d{4}'],
        defaults: ['year' => null],
    )]
    public function archive(?int $year = null): Response
    {
        return $this->render('activity/archive.html.twig');
    }

    #[Route(
        path: '/view/{activity}',
        name: 'view',
        requirements: ['activity' => '\d+'],
    )]
    public function view(int $activity): Response
    {
        return $this->render('activity/view.html.twig');
    }
}
