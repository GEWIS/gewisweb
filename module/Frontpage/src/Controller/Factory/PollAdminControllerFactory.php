<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PollAdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PollAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return PollAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): PollAdminController {
        return new PollAdminController(
            $container->get('frontpage_service_poll'),
        );
    }
}
