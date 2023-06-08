<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\CompanyController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CompanyControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
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
