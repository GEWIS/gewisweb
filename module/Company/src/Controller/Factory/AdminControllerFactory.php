<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\AdminController;
use Company\Service\AclService;
use Company\Service\Company as CompanyService;
use Company\Service\CompanyQuery as CompanyQueryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(CompanyService::class),
            $container->get(CompanyQueryService::class),
        );
    }
}
