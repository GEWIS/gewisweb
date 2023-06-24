<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\ApiController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiController {
        return new ApiController(
            $container->get('activity_service_acl'),
            $container->get('activity_service_activityQuery'),
        );
    }
}
