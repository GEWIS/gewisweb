<?php

namespace Company\Controller\Factory;

use Company\Controller\CompanyAccountController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CompanyAccountControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return CompanyAccountController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyAccountController {
        return new CompanyAccountController(
            $container->get('company_service_acl'),
        );
    }
}
