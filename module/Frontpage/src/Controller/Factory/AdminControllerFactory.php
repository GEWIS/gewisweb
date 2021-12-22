<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\AdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return AdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): AdminController {
        return new AdminController();
    }
}
