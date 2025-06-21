<?php

declare(strict_types=1);

namespace Company\Controller\Factory;

use Company\Controller\CompanyAccountController;
use Company\Form\JobsTransfer as JobsTransferForm;
use Company\Mapper\Job as JobMapper;
use Company\Mapper\Package as PackageMapper;
use Company\Service\AclService;
use Company\Service\Company as CompanyService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class CompanyAccountControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyAccountController {
        return new CompanyAccountController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(JobMapper::class),
            $container->get(PackageMapper::class),
            $container->get(JobsTransferForm::class),
            $container->get(CompanyService::class),
        );
    }
}
