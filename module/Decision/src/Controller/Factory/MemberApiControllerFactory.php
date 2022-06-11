<?php

namespace Decision\Controller\Factory;

use Decision\Controller\MemberApiController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MemberApiControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return MemberApiController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberApiController {
        return new MemberApiController(
            $container->get('decision_service_member'),
        );
    }
}
