<?php

namespace User\Mapper\Factory;

use User\Mapper\ApiApp;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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
