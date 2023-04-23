<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\ActivityController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ActivityControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ActivityController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityController {
        return new ActivityController(
            $container->get('activity_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('activity_service_activity'),
            $container->get('activity_service_activityQuery'),
            $container->get('activity_service_signup'),
            $container->get('activity_service_signupListQuery'),
        );
    }
}
