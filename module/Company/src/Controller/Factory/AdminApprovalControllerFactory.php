<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\AdminApprovalController;
use Company\Mapper\Company as CompanyMapper;
use Company\Mapper\Job as JobMapper;
use Company\Service\AclService;
use Company\Service\Company as CompanyService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminApprovalControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminApprovalController {
        return new AdminApprovalController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(CompanyMapper::class),
            $container->get(JobMapper::class),
            $container->get(CompanyService::class),
        );
    }
}
