<?php

namespace Activity\Controller\Factory;

use Activity\Controller\AdminApprovalController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminApprovalControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return AdminApprovalController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): AdminApprovalController {
        return new AdminApprovalController(
            $container->get('activity_service_activity'),
            $container->get('activity_service_activityQuery'),
            $container->get('activity_service_acl'),
            $container->get('translator'),
        );
    }
}
