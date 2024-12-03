<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\OrganAdminApprovalController;
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
            $container->get('decision_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('decision_mapper_organInformation'),
            $container->get('decision_service_organ'),
        );
    }
}
