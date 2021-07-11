<?php

namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use User\Controller\ApiAuthenticationController;
use User\Service\ApiApp;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiAuthenticationControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ApiAuthenticationController(
            $container->get('user_service_user'),
            $container->get(ApiApp::class)
        );
    }
}
