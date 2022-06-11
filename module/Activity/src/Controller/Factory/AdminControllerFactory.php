<?php

namespace Activity\Controller\Factory;

use Activity\Controller\AdminController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get('activity_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('activity_service_activity'),
            $container->get('activity_service_activityQuery'),
            $container->get('activity_service_signup'),
            $container->get('activity_service_signupListQuery'),
            $container->get('activity_mapper_signup'),
        );
    }
}
