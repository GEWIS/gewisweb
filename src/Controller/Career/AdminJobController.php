<?php

declare(strict_types=1);

namespace App\Controller\Career;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/career/jobs',
    name: 'admin/career/jobs/',
)]
class AdminJobController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('career/admin/jobs/index.html.twig');
    }
}
