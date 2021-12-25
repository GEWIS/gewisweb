<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PageController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return PageController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PageController {
        return new PageController(
            $container->get('frontpage_service_page'),
        );
    }
}
