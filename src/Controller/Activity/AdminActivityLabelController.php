<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/activities/labels',
    name: 'admin/activities/labels/',
)]
class AdminActivityLabelController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('activity/admin/labels/index.html.twig');
    }
}
