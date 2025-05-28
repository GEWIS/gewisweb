<?php

declare(strict_types=1);

namespace User\Command\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;
use User\Command\DeleteOldLoginAttempts;

class DeleteOldLoginAttemptsFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DeleteOldLoginAttempts {
        return new DeleteOldLoginAttempts($container->get(LoginAttemptService::class));
    }
}
