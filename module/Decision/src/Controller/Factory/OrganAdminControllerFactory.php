<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\OrganAdminController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OrganAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return OrganAdminController
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
