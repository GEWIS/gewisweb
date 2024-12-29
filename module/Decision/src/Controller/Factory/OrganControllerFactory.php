<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\OrganController;
use Decision\Service\Organ as OrganService;
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
            $container->get(OrganService::class),
        );
    }
}
