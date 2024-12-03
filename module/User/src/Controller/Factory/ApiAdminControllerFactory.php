<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\ApiAdminController;
use User\Service\ApiUser as ApiUserService;

class ApiAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiAdminController {
        return new ApiAdminController(
            $container->get(ApiUserService::class),
        );
    }
}
