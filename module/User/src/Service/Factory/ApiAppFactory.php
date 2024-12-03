<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Service\ApiApp;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiApp {
        return new ApiApp(
            $container->get(ApiAppAuthenticationMapper::class),
        );
    }
}
