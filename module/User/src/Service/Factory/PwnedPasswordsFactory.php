<?php

namespace User\Service\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Service\PwnedPasswords;

class PwnedPasswordsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return PwnedPasswords
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
