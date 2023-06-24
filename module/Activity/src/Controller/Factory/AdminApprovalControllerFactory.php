<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminApprovalController;
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
            $container->get('activity_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('activity_service_activity'),
            $container->get('activity_service_activityQuery'),
        );
    }
}
