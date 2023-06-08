<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

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
            $container->get('activity_service_activityQuery'),
            $container->get('decision_service_organ'),
        );
    }
}
