<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\InfimumController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class InfimumControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return InfimumController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InfimumController {
        return new InfimumController(
            $container->get('frontpage_service_acl'),
            $container->get('translator'),
            $container->get('application_service_infimum'),
        );
    }
}
