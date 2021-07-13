<?php

namespace User\Mapper\Factory;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use User\Mapper\ApiApp;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @return ApiApp
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ApiApp($serviceLocator->get('user_doctrine_em'));
    }
}
