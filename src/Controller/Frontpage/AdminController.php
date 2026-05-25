<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin',
    name: 'admin/',
)]
class AdminController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('frontpage/admin/index.html.twig');
    }
}
