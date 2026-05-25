<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\Enums\UserRoles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(
    attribute: UserRoles::Admin->value,
    message: 'You are not allowed to administer API users.',
)]
#[Route(
    path: '/admin/users/api',
    name: 'admin/users/api/',
)]
class AdminApiUserController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('user/admin/api/index.html.twig');
    }
}
