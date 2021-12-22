<?php

namespace Activity\Controller\Factory;

use Activity\Controller\AdminOptionController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminOptionControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return AdminOptionController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): AdminOptionController {
        return new AdminOptionController(
            $container->get('activity_service_acl'),
            $container->get('translator'),
            $container->get('activity_service_calendar'),
            $container->get('decision_service_organ'),
            $container->get('activity_mapper_period'),
        );
    }
}
