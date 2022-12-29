<?php

namespace Company\Controller\Factory;

use Company\Controller\CompanyAccountController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
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
            $container->get(MvcTranslator::class),
            $container->get('company_mapper_job'),
            $container->get('company_mapper_package'),
            $container->get('company_admin_jobsTransfer_form'),
            $container->get('company_service_company'),
        );
    }
}
