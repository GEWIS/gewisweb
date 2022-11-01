<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\OrganController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OrganControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return OrganController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganController {
        return new OrganController(
            $container->get('decision_service_acl'),
            $container->get('activity_service_activityQuery'),
            $container->get('decision_service_organ'),
        );
    }
}
