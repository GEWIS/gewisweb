<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/activities',
    name: 'admin/activities/',
)]
class AdminController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('activity/admin/index.html.twig');
    }

    #[Route(
        path: '/create',
        name: 'create',
    )]
    public function create(): Response
    {
        return $this->render('activity/admin/create.html.twig');
    }
}
