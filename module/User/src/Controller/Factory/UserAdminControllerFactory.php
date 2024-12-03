<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Decision\Mapper\Member as MemberMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\UserAdminController;
use User\Service\AclService;

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
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(MemberMapper::class),
        );
    }
}
