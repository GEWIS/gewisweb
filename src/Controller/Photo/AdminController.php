<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\User\Enums\UserRoles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(
    attribute: UserRoles::Board->value,
    message: 'You are not allowed to administer photos.',
)]
#[Route(
    path: '/admin/photos',
    name: 'admin/photos/',
)]
class AdminController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('photo/admin/index.html.twig');
    }
}
