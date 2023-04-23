<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\AdminController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get('company_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('company_service_company'),
            $container->get('company_service_companyquery'),
        );
    }
}
