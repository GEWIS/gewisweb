<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Service\PwnedPasswords;

class PwnedPasswordsFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PwnedPasswords {
        return new PwnedPasswords(
            $container->get('config')['passwords']['pwned_passwords_host'],
        );
    }
}
