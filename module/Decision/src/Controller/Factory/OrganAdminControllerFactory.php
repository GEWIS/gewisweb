<?php

namespace Decision\Controller\Factory;

use Decision\Controller\OrganAdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OrganAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return OrganAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): OrganAdminController {
        return new OrganAdminController(
            $container->get('decision_service_organ'),
        );
    }
}
