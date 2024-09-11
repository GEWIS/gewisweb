<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\UserAdminController;

class UserAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserAdminController {
        return new UserAdminController(
            $container->get('user_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('decision_mapper_member'),
        );
    }
}
