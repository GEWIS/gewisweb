<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PageAdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return PageAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): PageAdminController {
        return new PageAdminController(
            $container->get('frontpage_service_page'),
        );
    }
}
