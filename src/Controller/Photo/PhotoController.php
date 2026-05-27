<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\User\Enums\UserRoles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to view photos.',
)]
#[Route(
    path: '/photos',
    name: 'photo/',
)]
class PhotoController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('photo/index.html.twig');
    }

    #[Route(
        path: '/weekly',
        name: 'weekly',
    )]
    public function weekly(): Response
    {
        return $this->render('photo/weekly.html.twig');
    }

    #[Route(
        path: '/{type}/{album}',
        name: 'album',
        requirements: [
            'type' => 'album|member|weekly',
            'album' => '\d+',
        ],
    )]
    public function album(
        string $type,
        int $album,
    ): Response {
        return $this->render('photo/album.html.twig');
    }
}
