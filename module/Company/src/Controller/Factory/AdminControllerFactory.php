<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\AdminController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
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
