<?php

namespace Activity\Controller\Factory;

use Activity\Controller\AdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get('activity_service_acl'),
            $container->get('translator'),
            $container->get('activity_service_activity'),
            $container->get('activity_service_activityQuery'),
            $container->get('activity_service_signup'),
            $container->get('activity_service_signupListQuery'),
            $container->get('activity_mapper_signup'),
        );
    }
}
