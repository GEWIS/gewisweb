<?php

namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Controller\ApiAuthenticationController;
use User\Service\ApiApp;

class ApiAuthenticationControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ApiAuthenticationController(
            $container->get('user_service_user'),
            $container->get(ApiApp::class),
            $container->get('user_service_acl')
        );
    }
}
