<?php

namespace Activity\Controller\Factory;

use Activity\Controller\AdminCategoryController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminCategoryControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return AdminCategoryController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): AdminCategoryController {
        return new AdminCategoryController(
            $container->get('activity_service_acl'),
            $container->get('translator'),
            $container->get('activity_service_category'),
        );
    }
}
