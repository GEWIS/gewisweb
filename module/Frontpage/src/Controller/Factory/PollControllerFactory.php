<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PollController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PollControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return PollController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): PollController {
        return new PollController(
            $container->get('frontpage_form_poll_comment'),
            $container->get('frontpage_service_poll'),
            $container->get('frontpage_service_acl'),
            $container->get('translator'),
        );
    }
}
