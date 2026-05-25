<?php

declare(strict_types=1);

namespace App\Controller\Education;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/education',
    name: 'admin/education/',
)]
class AdminController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('education/admin/index.html.twig');
    }

    #[Route(
        path: '/exams/upload',
        name: 'exams/upload',
    )]
    public function upload(): Response
    {
        return $this->render('education/admin/exams-upload.html.twig');
    }
}
