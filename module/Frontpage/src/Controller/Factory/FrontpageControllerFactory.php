<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\FrontpageController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FrontpageControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return FrontpageController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FrontpageController {
        return new FrontpageController(
            $container->get('frontpage_service_frontpage'),
        );
    }
}
