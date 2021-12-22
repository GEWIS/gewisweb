<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\FrontpageController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FrontpageControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return FrontpageController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): FrontpageController {
        return new FrontpageController(
            $container->get('frontpage_service_frontpage'),
        );
    }
}
