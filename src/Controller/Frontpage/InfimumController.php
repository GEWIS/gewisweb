<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Entity\User\Enums\UserRoles;
use App\Service\Frontpage\InfimumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The infimum, asked for by the page after it has been drawn rather than while it is being drawn. It comes from the
 * Supremum's own API, and the footer is on every page of this website: fetched inline, somebody else's slow morning
 * would be a slow morning here.
 *
 * The cron keeps the cache filled, so this usually answers from it; a cold cache fetches once, off the render path.
 */
#[IsGranted(UserRoles::User->value)]
class InfimumController extends AbstractController
{
    public function __construct(private readonly InfimumService $infimumService)
    {
    }

    #[Route(
        path: '/infimum',
        name: 'infimum',
        methods: ['GET'],
    )]
    public function show(): JsonResponse
    {
        return new JsonResponse(['content' => $this->infimumService->getInfimum()]);
    }
}
