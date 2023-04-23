<?php

declare(strict_types=1);

namespace User\Mapper\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Mapper\ApiApp;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ApiApp
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiApp {
        return new ApiApp(
            $container->get('doctrine.entitymanager.orm_default'),
        );
    }
}
