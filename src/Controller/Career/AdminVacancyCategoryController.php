<?php

declare(strict_types=1);

namespace App\Controller\Career;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/career/vacancies/categories',
    name: 'admin/career/vacancies/categories/',
)]
class AdminVacancyCategoryController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('career/admin/vacancies/categories/index.html.twig');
    }
}
