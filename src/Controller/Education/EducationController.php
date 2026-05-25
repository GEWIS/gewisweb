<?php

declare(strict_types=1);

namespace App\Controller\Education;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/education',
    name: 'education/',
)]
class EducationController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('education/index.html.twig');
    }
}
