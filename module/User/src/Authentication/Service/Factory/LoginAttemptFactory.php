<?php

declare(strict_types=1);

namespace User\Authentication\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;
use User\Mapper\LoginAttempt as LoginAttemptMapper;

class LoginAttemptFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): LoginAttemptService {
        return new LoginAttemptService(
            $container->get('user_remoteaddress'),
            $container->get(LoginAttemptMapper::class),
            $container->get('config')['login_rate_limits'],
        );
    }
}
