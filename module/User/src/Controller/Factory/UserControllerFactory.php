<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Controller\UserController;
use User\Service\AclService;
use User\Service\User as UserService;

class UserControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserController {
        return new UserController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(UserService::class),
        );
    }
}
