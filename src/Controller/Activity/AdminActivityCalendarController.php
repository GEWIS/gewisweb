<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/activities/calendar',
    name: 'admin/activities/calendar/',
)]
class AdminActivityCalendarController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('activity/admin/calendar/index.html.twig');
    }
}
