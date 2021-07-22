<?php

namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Controller\ApiController;

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
        array $options = null
    ): ApiController {
        return new ApiController(
            $container->get('decision_service_memberinfo'),
            $container->get('user_service_acl')
        );
    }
}
