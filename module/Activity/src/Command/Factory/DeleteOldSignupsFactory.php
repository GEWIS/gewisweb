<?php

declare(strict_types=1);

namespace Activity\Command\Factory;

use Activity\Command\DeleteOldSignups;
use Activity\Service\Signup as SignupService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DeleteOldSignupsFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DeleteOldSignups {
        return new DeleteOldSignups($container->get(SignupService::class));
    }
}
