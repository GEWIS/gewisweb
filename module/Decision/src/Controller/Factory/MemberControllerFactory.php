<?php

namespace Decision\Controller\Factory;

use Decision\Controller\MemberController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MemberControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return MemberController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): MemberController {
        return new MemberController(
            $container->get('decision_service_acl'),
            $container->get('decision_service_member'),
            $container->get('decision_service_memberinfo'),
            $container->get('decision_service_decision'),
            $container->get('config')['regulations'],
        );
    }
}
