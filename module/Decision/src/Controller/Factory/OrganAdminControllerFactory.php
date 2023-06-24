<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\OrganAdminController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganAdminController {
        return new OrganAdminController(
            $container->get('decision_service_organ'),
        );
    }
}
