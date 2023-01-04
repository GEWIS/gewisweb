<?php

namespace Company\Controller\Factory;

use Company\Controller\AdminApprovalController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminApprovalControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AdminApprovalController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminApprovalController {
        return new AdminApprovalController(
            $container->get('company_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('company_mapper_company'),
            $container->get('company_mapper_job'),
            $container->get('company_service_company'),
        );
    }
}
