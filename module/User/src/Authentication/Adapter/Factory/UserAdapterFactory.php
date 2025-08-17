<?php

declare(strict_types=1);

namespace User\Authentication\Adapter\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;
use User\Mapper\User as UserMapper;
use User\Service\PwnedPasswords as PwnedPasswordsService;

class UserAdapterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserAdapter {
        return new UserAdapter(
            $container->get(MvcTranslator::class),
            $container->get('user_bcrypt'),
            $container->get(LoginAttemptService::class),
            $container->get(PwnedPasswordsService::class),
            $container->get(UserMapper::class),
        );
    }
}
