<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/decision/bodies',
    name: 'admin/decision/bodies/',
)]
class AdminBodyController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('decision/admin/bodies/index.html.twig');
    }
}
