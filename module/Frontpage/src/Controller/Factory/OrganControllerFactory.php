<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\OrganController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OrganControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return OrganController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): OrganController {
        return new OrganController(
            $container->get('activity_service_activityQuery'),
            $container->get('decision_service_organ'),
        );
    }
}
