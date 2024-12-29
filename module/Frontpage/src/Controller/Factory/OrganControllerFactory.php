<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Activity\Service\ActivityQuery as ActivityQueryService;
use Decision\Service\Organ as OrganService;
use Frontpage\Controller\OrganController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganController {
        return new OrganController(
            $container->get(ActivityQueryService::class),
            $container->get(OrganService::class),
        );
    }
}
