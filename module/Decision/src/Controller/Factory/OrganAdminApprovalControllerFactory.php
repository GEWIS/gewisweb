<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\OrganAdminApprovalController;
use Decision\Mapper\OrganInformation as OrganInformationMapper;
use Decision\Service\AclService;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganAdminApprovalControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganAdminApprovalController {
        return new OrganAdminApprovalController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(OrganInformationMapper::class),
            $container->get(OrganService::class),
        );
    }
}
