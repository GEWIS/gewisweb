<?php

namespace User\Mapper\Factory;

use User\Mapper\ApiApp;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiApp
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ApiApp($serviceLocator->get('user_doctrine_em'));
    }
}
