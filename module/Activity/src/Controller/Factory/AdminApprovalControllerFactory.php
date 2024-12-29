<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminApprovalController;
use Activity\Service\AclService;
use Activity\Service\Activity as ActivityService;
use Activity\Service\ActivityQuery as ActivityQueryService;
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
            $container->get(ActivityService::class),
            $container->get(ActivityQueryService::class),
        );
    }
}
