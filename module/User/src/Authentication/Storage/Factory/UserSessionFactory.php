<?php

declare(strict_types=1);

namespace User\Authentication\Storage\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Authentication\Storage\UserSession;

class UserSessionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserSession {
        return new UserSession(
            $container->get('Request'),
            $container->get('Response'),
            $container->get('config'),
        );
    }
}
