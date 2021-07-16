<?php

namespace User\Mapper\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Mapper\ApiApp;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @return ApiApp
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ApiApp($container->get('user_doctrine_em'));
    }
}
