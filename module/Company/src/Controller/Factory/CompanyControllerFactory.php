<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\CompanyController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CompanyControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return CompanyController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyController {
        return new CompanyController(
            $container->get('company_service_company'),
            $container->get('company_service_companyquery'),
        );
    }
}
