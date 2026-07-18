<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Service\User\ExternalAppService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class JwksController extends AbstractController
{
    /**
     * Public JWKS endpoint, routed locale-less from {@see /config/routes.yaml}, that external applications on a modern
     * profile fetch to verify their signed tokens.
     */
    public function jwks(ExternalAppService $externalAppService): JsonResponse
    {
        return new JsonResponse($externalAppService->publicKeySet());
    }
}
