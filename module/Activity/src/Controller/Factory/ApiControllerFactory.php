<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\ApiController;
use Activity\Service\AclService;
use Activity\Service\ActivityQuery;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiController {
        return new ApiController(
            $container->get(AclService::class),
            $container->get(ActivityQuery::class),
        );
    }
}
