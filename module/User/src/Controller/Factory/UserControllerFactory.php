<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\UserController;

class UserControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return UserController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserController {
        return new UserController(
            $container->get('user_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('user_service_user'),
        );
    }
}
