<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminOptionController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminOptionControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminOptionController {
        return new AdminOptionController(
            $container->get('activity_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('activity_service_calendar'),
            $container->get('decision_service_organ'),
            $container->get('activity_mapper_period'),
        );
    }
}
