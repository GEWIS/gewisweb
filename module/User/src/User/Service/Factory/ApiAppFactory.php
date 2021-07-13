<?php

namespace User\Service\Factory;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use User\Service\ApiApp;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @return ApiApp
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ApiApp($serviceLocator->get(\User\Mapper\ApiApp::class));
    }
}
