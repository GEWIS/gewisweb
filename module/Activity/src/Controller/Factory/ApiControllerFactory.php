<?php

namespace Activity\Controller\Factory;

use Activity\Controller\ApiController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return ApiController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiController {
        return new ApiController(
            $container->get('activity_service_activityQuery'),
            $container->get('activity_service_acl'),
        );
    }
}
